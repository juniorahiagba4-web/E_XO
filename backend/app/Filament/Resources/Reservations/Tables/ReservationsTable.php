<?php

namespace App\Filament\Resources\Reservations\Tables;

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
                    ->formatStateUsing(fn (string $state) => $state === 'purchase' ? 'Achat' : 'Location')
                    ->color(fn (string $state) => $state === 'purchase' ? 'success' : 'info'),
                TextColumn::make('event_start_date')
                    ->label('Début')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('event_end_date')
                    ->label('Fin')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('delivery_method')
                    ->label('Livraison')
                    ->formatStateUsing(fn (string $state) => $state === 'delivery' ? 'Livraison' : 'Retrait'),
                TextColumn::make('total')
                    ->label('Total')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('currency')
                    ->label('Devise'),
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
                    ->options([
                        'draft' => 'Brouillon',
                        'quote_sent' => 'Devis envoyé',
                        'confirmed' => 'Confirmée',
                        'deposit_paid' => 'Acompte payé',
                        'ongoing' => 'En cours',
                        'completed' => 'Terminée',
                        'cancelled' => 'Annulée',
                    ]),
                SelectFilter::make('type')
                    ->label('Type')
                    ->options(['rental' => 'Location', 'purchase' => 'Achat']),
            ])
            ->recordActions([
                Action::make('quote_pdf')
                    ->label('Devis PDF')
                    ->icon('heroicon-o-document-arrow-down')
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
}
