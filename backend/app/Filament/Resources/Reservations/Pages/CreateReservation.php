<?php

namespace App\Filament\Resources\Reservations\Pages;

use App\Exceptions\InsufficientStockException;
use App\Filament\Resources\Reservations\ReservationResource;
use App\Filament\Resources\Reservations\Schemas\ManualOrderForm;
use App\Models\Customer;
use App\Models\Item;
use App\Models\Reservation;
use App\Services\QuotePdfService;
use App\Services\ReservationService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

/**
 * An order taken by phone or in the shop. It goes through the same service
 * as the website, so stock is checked and locked the same way, then the
 * prices typed by the team replace the catalogue ones.
 */
class CreateReservation extends CreateRecord
{
    protected static string $resource = ReservationResource::class;

    protected static bool $canCreateAnother = false;

    public function getTitle(): string
    {
        return 'Nouvelle commande';
    }

    public function form(Schema $schema): Schema
    {
        return ManualOrderForm::configure($schema);
    }

    protected function handleRecordCreation(array $data): Model
    {
        $isRental = $data['type'] === 'rental';
        $delivered = $data['delivery_method'] === 'delivery';

        // The same item added twice becomes one line.
        $lines = collect($data['lines'] ?? [])
            ->filter(fn (array $line) => filled($line['item_id'] ?? null))
            ->groupBy('item_id')
            ->map(fn ($group, $itemId) => [
                'item_id' => (int) $itemId,
                'quantity' => (int) $group->sum('quantity'),
                'unit_price' => $group->first()['unit_price'] ?? null,
            ])
            ->values();

        try {
            $reservation = app(ReservationService::class)->createOrder([
                'type' => $data['type'],
                'event_start_date' => $isRental ? $data['event_start_date'] : null,
                'event_end_date' => $isRental ? $data['event_end_date'] : null,
                'delivery_method' => $data['delivery_method'],
                'delivery_address' => $delivered ? ($data['delivery_address'] ?? null) : null,
                'delivery_time' => $delivered ? ($data['delivery_time'] ?? null) : null,
                'delivery_date' => ! $isRental && $delivered ? ($data['delivery_date'] ?? null) : null,
                'notes' => $data['notes'] ?? null,
                'items' => $lines->map(fn ($line) => ['item_id' => $line['item_id'], 'quantity' => $line['quantity']])->all(),
            ], Customer::query()->findOrFail($data['customer_id']));
        } catch (InsufficientStockException $e) {
            $name = Item::query()->find($e->itemId)?->name_fr ?? 'Un article';
            Notification::make()
                ->danger()
                ->title('Stock insuffisant')
                ->body("« {$name} » : {$e->requestedQuantity} demandé(s), {$e->availableQuantity} disponible(s)"
                    .($isRental ? ' sur ces dates.' : '.'))
                ->persistent()
                ->send();

            $this->halt();
        }

        foreach ($reservation->items as $line) {
            $price = $lines->firstWhere('item_id', $line->item_id)['unit_price'] ?? null;
            if ($isRental) {
                if (filled($price)) {
                    $line->update(['unit_price_per_day' => $price]);
                }
            } else {
                // Left empty: the price stays "sur devis" until it is typed in.
                $line->update(['unit_sale_price' => filled($price) ? $price : null]);
            }
        }

        $reservation->update([
            'status' => $data['status'],
            'discount' => (float) ($data['discount'] ?? 0),
            'purchase_date' => $isRental ? null : ($data['purchase_date'] ?? now()->toDateString()),
            'return_time' => $isRental && $delivered ? ($data['return_time'] ?? $data['delivery_time'] ?? null) : null,
            'hold_expires_at' => $data['status'] === 'quote_sent' ? $reservation->hold_expires_at : null,
        ]);

        $reservation->recalculateTotals();
        app(QuotePdfService::class)->refresh($reservation);

        return $reservation->refresh();
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()->label('Enregistrer la commande');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Commande enregistrée';
    }

    protected function getRedirectUrl(): string
    {
        /** @var Reservation $record */
        $record = $this->getRecord();

        return ReservationResource::getUrl('edit', ['record' => $record]);
    }
}
