<?php

namespace App\Filament\Resources\Reservations\RelationManagers;

use App\Models\Reservation;
use App\Models\ReservationItem;
use App\Services\QuotePdfService;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Livewire\Attributes\On;

/**
 * Lines are created by ReservationService (under the stock lock), so items
 * and quantities cannot be changed here. Only the unit price can: that is how
 * the team sets the price of a "sur devis" item, which then flows into the
 * totals and the PDF.
 */
class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Articles réservés';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        /** @var Reservation $reservation */
        $reservation = $this->getOwnerRecord();
        $isRental = $reservation->type === 'rental';
        $days = $reservation->rentalDays();

        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('item.name_fr')
                    ->label('Article'),
                TextColumn::make('quantity')
                    ->label('Quantité')
                    ->numeric(),
                TextColumn::make('unit_price')
                    ->label($isRental ? 'Prix / jour' : 'Prix unitaire')
                    // Filament skips formatting for empty states, hence the marker.
                    ->getStateUsing(fn (ReservationItem $record) => static::unitPrice($record, $isRental) ?? 'pending')
                    ->formatStateUsing(fn ($state) => $state === 'pending' ? 'Sur devis : prix à saisir' : static::money($state))
                    ->badge(fn ($state) => $state === 'pending')
                    ->color(fn ($state) => $state === 'pending' ? 'warning' : null),
                TextColumn::make('days')
                    ->label('Jours')
                    ->state($days ?? '—')
                    ->visible($isRental),
                TextColumn::make('subtotal')
                    ->label('Sous-total')
                    ->formatStateUsing(fn ($state, ReservationItem $record) => static::unitPrice($record, $isRental) === null ? '—' : static::money($state))
                    ->description(function (ReservationItem $record) use ($isRental, $days) {
                        $price = static::unitPrice($record, $isRental);
                        if ($price === null) {
                            return null;
                        }
                        $formula = $record->quantity.' × '.static::money($price);

                        return $isRental ? $formula.' × '.($days ?? 1).' j' : $formula;
                    }),
            ])
            ->headerActions([])
            ->recordActions([
                Action::make('set_price')
                    ->label(fn (ReservationItem $record) => static::unitPrice($record, $isRental) === null ? 'Saisir le prix' : 'Modifier le prix')
                    ->icon('heroicon-o-pencil-square')
                    ->color(fn (ReservationItem $record) => static::unitPrice($record, $isRental) === null ? 'warning' : 'gray')
                    ->modalHeading(fn (ReservationItem $record) => 'Prix de « '.$record->item?->name_fr.' »')
                    ->modalDescription('Le sous-total, le total et le PDF envoyé au client sont recalculés automatiquement.')
                    ->modalSubmitActionLabel('Enregistrer')
                    ->fillForm(fn (ReservationItem $record) => ['price' => static::unitPrice($record, $isRental)])
                    ->schema([
                        TextInput::make('price')
                            ->label($isRental ? 'Prix par jour et par unité' : 'Prix unitaire')
                            ->numeric()
                            ->minValue(0)
                            ->suffix('FCFA')
                            ->required(),
                    ])
                    ->action(function (ReservationItem $record, array $data) use ($isRental) {
                        $record->update([$isRental ? 'unit_price_per_day' : 'unit_sale_price' => $data['price']]);

                        /** @var Reservation $reservation */
                        $reservation = $this->getOwnerRecord();
                        $reservation->recalculateTotals();
                        app(QuotePdfService::class)->refresh($reservation);

                        // Lets the page above show the new amounts.
                        $this->dispatch('reservation-lines-updated');
                    })
                    ->successNotificationTitle('Prix enregistré, total et PDF mis à jour'),
            ])
            ->toolbarActions([]);
    }

    /** New rental dates change the days billed: redraw with them. */
    #[On('reservation-saved')]
    public function refreshOwnerRecord(): void
    {
        $this->getOwnerRecord()->refresh();
    }

    private static function unitPrice(ReservationItem $record, bool $isRental): ?string
    {
        return $isRental ? $record->unit_price_per_day : $record->unit_sale_price;
    }

    private static function money(mixed $amount): string
    {
        return number_format((float) $amount, 0, ',', ' ').' FCFA';
    }
}
