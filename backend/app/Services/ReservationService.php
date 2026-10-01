<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Customer;
use App\Models\Item;
use App\Models\Promotion;
use App\Models\Reservation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ReservationService
{
    public function __construct(
        private readonly AvailabilityService $availability,
        private readonly QuotePdfService $pdfService,
    ) {}

    /**
     * Create a rental quote or a firm purchase order. Every line's stock is
     * re-checked under a row lock so two concurrent requests can never both
     * win the same units, then the order is persisted and its PDF generated.
     *
     * @throws InsufficientStockException
     */
    public function createOrder(array $data, Customer $customer): Reservation
    {
        return DB::transaction(function () use ($data, $customer) {
            $type = $data['type'];
            $isRental = $type === 'rental';

            $start = $isRental ? Carbon::parse($data['event_start_date'])->startOfDay() : null;
            $end = $isRental ? Carbon::parse($data['event_end_date'])->startOfDay() : null;
            $nights = $isRental ? max(1, $start->diffInDays($end)) : null;

            $itemIds = collect($data['items'])->pluck('item_id')->unique()->sort()->values();

            // Lock in a stable order to avoid deadlocks between concurrent orders
            // that share some but not all of the same items.
            $items = Item::query()->whereIn('id', $itemIds)->lockForUpdate()->get()->keyBy('id');

            foreach ($data['items'] as $line) {
                $item = $items->get($line['item_id']);

                if (! $item) {
                    throw new InvalidArgumentException("Item {$line['item_id']} not found.");
                }

                $available = $isRental
                    ? $this->availability->availableQuantity($item, $start, $end)
                    : $this->availability->purchasableQuantity($item);

                if ($line['quantity'] > $available) {
                    throw new InsufficientStockException($item->id, $line['quantity'], $available);
                }
            }

            $subtotal = 0;
            $lines = [];

            foreach ($data['items'] as $line) {
                $item = $items->get($line['item_id']);

                if ($isRental) {
                    $lineSubtotal = $item->rental_price_per_day * $line['quantity'] * $nights;

                    $lines[] = [
                        'item_id' => $item->id,
                        'quantity' => $line['quantity'],
                        'unit_price_per_day' => $item->rental_price_per_day,
                        'subtotal' => $lineSubtotal,
                    ];
                } else {
                    if (! $item->sale_price && ! $item->sale_price_on_request) {
                        throw new InvalidArgumentException("Item {$item->id} is not for sale.");
                    }

                    // A "sur devis" item has no fixed price yet, so it contributes
                    // nothing to the computed total — the final price is agreed
                    // with the customer separately, and the quote clearly marks
                    // the line as pending (see ReservationResource/quote PDF).
                    $lineSubtotal = $item->sale_price ? $item->sale_price * $line['quantity'] : 0;

                    $lines[] = [
                        'item_id' => $item->id,
                        'quantity' => $line['quantity'],
                        'unit_sale_price' => $item->sale_price,
                        'subtotal' => $lineSubtotal,
                    ];
                }

                $subtotal += $lineSubtotal;
            }

            $discount = $this->resolveDiscount($data['promo_code'] ?? null, $lines, $items);

            $reservation = Reservation::query()->create([
                'customer_id' => $customer->id,
                'status' => 'quote_sent',
                'type' => $type,
                'event_start_date' => $start,
                'event_end_date' => $end,
                'delivery_method' => $data['delivery_method'],
                'delivery_address' => $data['delivery_address'] ?? null,
                'delivery_slot_template_id' => $data['delivery_slot_id'] ?? null,
                'delivery_date' => $start,
                'return_slot_template_id' => $data['return_slot_id'] ?? null,
                'return_date' => $end,
                'notes' => $data['notes'] ?? null,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => max(0, $subtotal - $discount),
                'currency' => 'XOF',
                'hold_expires_at' => $isRental ? now()->addHours(Reservation::QUOTE_HOLD_HOURS) : null,
            ]);

            $reservation->items()->createMany($lines);

            $pdfPath = $this->pdfService->generate($reservation->fresh(['items.item', 'customer']));
            $reservation->update(['quote_pdf_path' => $pdfPath, 'quote_generated_at' => now()]);

            return $reservation->fresh(['items.item', 'customer']);
        });
    }

    /**
     * Recomputes the discount server-side from the promo code — the client
     * only ever sees a preview via PromotionController::validateCode(),
     * never a trusted amount. An invalid/expired/inapplicable code is
     * silently worth 0 rather than failing the whole order, since the code
     * may simply have expired between the client's preview and this request.
     *
     * @param  array<int, array{item_id: int, quantity: int, subtotal: float|int}>  $lines
     * @param  \Illuminate\Support\Collection<int, Item>  $items
     */
    private function resolveDiscount(?string $code, array $lines, $items): float
    {
        if (! $code) {
            return 0;
        }

        $promotion = Promotion::query()->currentlyActive()->forCode($code)->first();

        if (! $promotion) {
            return 0;
        }

        $applicableSubtotal = 0;

        foreach ($lines as $line) {
            $item = $items->get($line['item_id']);

            if ($item && $promotion->appliesToItem($item)) {
                $applicableSubtotal += $line['subtotal'];
            }
        }

        if ($applicableSubtotal <= 0) {
            return 0;
        }

        return $promotion->discount_type === 'percent'
            ? round($applicableSubtotal * ((float) $promotion->discount_value / 100), 2)
            : min((float) $promotion->discount_value, $applicableSubtotal);
    }
}
