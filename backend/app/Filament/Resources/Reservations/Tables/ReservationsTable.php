<?php

namespace App\Filament\Resources\Reservations\Tables;

use App\Models\Reservation;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

class ReservationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('customer'))
            ->columns([
                TextColumn::make('reference')
                    ->label('Référence')
                    ->searchable()
                    ->fontFamily('mono'),
                TextColumn::make('customer.full_name')
                    ->label('Client')
                    ->searchable(['customers.first_name', 'customers.last_name']),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => Reservation::STATUS_LABELS[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        'quote_sent' => 'warning',
                        'confirmed' => 'info',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => Reservation::TYPE_LABELS[$state] ?? $state)
                    ->color(fn (string $state) => $state === 'purchase' ? 'success' : 'info'),
                // A rental shows its period, a purchase the day it was bought.
                TextColumn::make('dates')
                    ->label('Dates')
                    ->state(fn (Reservation $record) => $record->type === 'rental'
                        ? $record->event_start_date?->format('d/m/Y').' → '.$record->event_end_date?->format('d/m/Y')
                        : 'Achat le '.($record->purchase_date ?? $record->created_at)?->format('d/m/Y'))
                    ->description(fn (Reservation $record) => ($days = $record->rentalDays())
                        ? $days.' '.($days > 1 ? 'jours' : 'jour')
                        : null)
                    ->sortable(query: fn (Builder $query, string $direction) => $query
                        ->orderByRaw('COALESCE(event_start_date, purchase_date) '.($direction === 'asc' ? 'asc' : 'desc'))),
                TextColumn::make('delivery_method')
                    ->label('Livraison')
                    ->formatStateUsing(fn (string $state) => $state === 'delivery' ? 'À domicile' : 'Retrait en boutique')
                    ->description(fn (Reservation $record) => static::deliveryDetails($record))
                    ->wrap()
                    ->extraAttributes(['style' => 'min-width: 15rem']),
                TextColumn::make('total')
                    ->label('Total')
                    ->numeric(decimalPlaces: 0, thousandsSeparator: ' ')
                    ->suffix(' FCFA')
                    ->sortable(),
                // Usable only once the order is finished: before that the link
                // stays grey and does nothing.
                TextColumn::make('review_request')
                    ->label('Avis')
                    ->state('Demander un avis')
                    ->icon(Heroicon::OutlinedStar)
                    ->color(fn (Reservation $record) => static::canRequestReview($record) ? 'success' : 'gray')
                    ->weight(fn (Reservation $record) => static::canRequestReview($record) ? 'semibold' : null)
                    ->extraAttributes(fn (Reservation $record) => static::canRequestReview($record)
                        ? []
                        : ['class' => 'opacity-50 cursor-not-allowed', 'title' => 'Disponible quand la réservation est terminée'])
                    ->url(fn (Reservation $record) => static::canRequestReview($record) ? $record->reviewWhatsappUrl() : null)
                    ->openUrlInNewTab(),
                TextColumn::make('created_at')
                    ->label('Reçue le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options(Reservation::STATUS_LABELS),
                SelectFilter::make('type')
                    ->label('Type')
                    ->options(Reservation::TYPE_LABELS),
                SelectFilter::make('delivery_method')
                    ->label('Livraison')
                    ->options(Reservation::DELIVERY_METHOD_LABELS),
            ])
            ->recordActions([
                Action::make('quote_pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('gray')
                    ->url(fn ($record) => $record->quote_pdf_path ? Storage::disk('public')->url($record->quote_pdf_path) : null)
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => filled($record->quote_pdf_path)),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function canRequestReview(Reservation $record): bool
    {
        return $record->canBeReviewed() && filled($record->customer?->phone);
    }

    /** One line each: outbound, return (rentals) and address. */
    private static function deliveryDetails(Reservation $record): ?HtmlString
    {
        if ($record->delivery_method !== 'delivery') {
            return null;
        }

        $outbound = $record->deliveryWish();
        $return = $record->returnWish();

        $lines = collect([
            $outbound ? ($return ? 'Aller ' : 'Livraison ').$outbound : null,
            $return ? 'Retour '.$return : null,
            $record->delivery_address,
        ])->filter()->map(fn (string $line) => e($line));

        return $lines->isEmpty() ? null : new HtmlString($lines->join('<br>'));
    }
}
