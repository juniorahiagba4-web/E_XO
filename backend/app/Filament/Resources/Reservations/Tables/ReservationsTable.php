<?php

namespace App\Filament\Resources\Reservations\Tables;

use App\Models\Reservation;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class ReservationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
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
                        'draft' => 'gray',
                        'quote_sent' => 'warning',
                        'confirmed', 'deposit_paid' => 'info',
                        'ongoing' => 'primary',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => Reservation::TYPE_LABELS[$state] ?? $state)
                    ->color(fn (string $state) => $state === 'purchase' ? 'success' : 'info'),
                TextColumn::make('event_start_date')
                    ->label('Début')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('event_end_date')
                    ->label('Fin')
                    ->date('d/m/Y')
                    ->sortable(),
                // "Livraison" with the address and wished time underneath, so the
                // team sees at a glance where and when to deliver.
                TextColumn::make('delivery_method')
                    ->label('Livraison')
                    ->formatStateUsing(fn (string $state) => $state === 'delivery' ? 'À domicile' : 'Retrait en boutique')
                    ->description(fn (Reservation $record) => $record->delivery_method === 'delivery'
                        ? collect([$record->deliveryWish(), $record->delivery_address])->filter()->join(' — ')
                        : null)
                    ->wrap(),
                TextColumn::make('total')
                    ->label('Total')
                    ->numeric(decimalPlaces: 0, thousandsSeparator: ' ')
                    ->suffix(' FCFA')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Créée le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options(Reservation::STATUS_LABELS),
                SelectFilter::make('delivery_method')
                    ->label('Livraison')
                    ->options(Reservation::DELIVERY_METHOD_LABELS),
                SelectFilter::make('type')
                    ->label('Type')
                    ->options(Reservation::TYPE_LABELS),
            ])
            ->recordActions([
                Action::make('quote_pdf')
                    ->label('Devis PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->url(fn ($record) => $record->quote_pdf_path ? Storage::disk('public')->url($record->quote_pdf_path) : null)
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => filled($record->quote_pdf_path)),
                // Opens WhatsApp with a pre-filled message carrying the customer's
                // private review link. Only finished orders can be reviewed.
                Action::make('request_review')
                    ->label('Demander un avis')
                    ->icon('heroicon-o-star')
                    ->color('success')
                    ->url(fn ($record) => $record->reviewWhatsappUrl())
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => $record->canBeReviewed() && filled($record->customer?->phone)),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
