<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Customer;
use App\Models\Item;
use App\Models\Reservation;
use Illuminate\Support\Arr;
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
     * Create a quote (devis): validates real-time availability for every line
     * under a row lock so two concurrent requests can never both win the same
     * stock, persists the reservation, then generates its PDF.
     *
     * @throws InsufficientStockException
     */
    public function createQuote(array $data): Reservation
    {
        return DB::transaction(function () use ($data) {
            $start = Carbon::parse($data['event_start_date'])->startOfDay();
            $end = Carbon::parse($data['event_end_date'])->startOfDay();
            $nights = max(1, $start->diffInDays($end));

            $itemIds = collect($data['items'])->pluck('item_id')->unique()->sort()->values();

            // Lock in a stable order to avoid deadlocks between concurrent bookings
            // that share some but not all of the same items.
            $items = Item::query()->whereIn('id', $itemIds)->lockForUpdate()->get()->keyBy('id');

            foreach ($data['items'] as $line) {
                $item = $items->get($line['item_id']);

                if (! $item) {
                    throw new InvalidArgumentException("Item {$line['item_id']} not found.");
                }

                $available = $this->availability->availableQuantity($item, $start, $end);

                if ($line['quantity'] > $available) {
                    throw new InsufficientStockException($item->id, $line['quantity'], $available);
                }
            }

            $customer = Customer::query()->updateOrCreate(
                ['email' => $data['customer']['email']],
                Arr::only($data['customer'], ['first_name', 'last_name', 'phone']),
            );

            $subtotal = 0;
            $depositRequired = 0;
            $lines = [];

            foreach ($data['items'] as $line) {
                $item = $items->get($line['item_id']);
                $lineSubtotal = $item->rental_price_per_day * $line['quantity'] * $nights;

                $subtotal += $lineSubtotal;
                $depositRequired += ($item->deposit_amount ?? 0) * $line['quantity'];

                $lines[] = [
                    'item_id' => $item->id,
                    'quantity' => $line['quantity'],
                    'unit_price_per_day' => $item->rental_price_per_day,
                    'subtotal' => $lineSubtotal,
                ];
            }

            $reservation = Reservation::query()->create([
                'customer_id' => $customer->id,
                'status' => 'quote_sent',
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
                'deposit_required' => $depositRequired,
                'discount' => 0,
                'total' => $subtotal,
                'currency' => 'XOF',
                'hold_expires_at' => now()->addHours(Reservation::QUOTE_HOLD_HOURS),
            ]);

            $reservation->items()->createMany($lines);

            $pdfPath = $this->pdfService->generate($reservation->fresh(['items.item', 'customer']));
            $reservation->update(['quote_pdf_path' => $pdfPath, 'quote_generated_at' => now()]);

            return $reservation->fresh(['items.item', 'customer']);
        });
    }
}
