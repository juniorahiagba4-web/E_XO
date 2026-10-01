<?php

namespace App\Filament\Resources\Items\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class ItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image_path')->label(''),
                TextColumn::make('name_fr')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category.name_fr')
                    ->label('Catégorie')
                    ->searchable(),
                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable(),
                TextColumn::make('rental_price_per_day')
                    ->label('Prix / jour')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('sale_price')
                    ->label('Prix de vente')
                    ->numeric()
                    ->placeholder('—')
                    ->formatStateUsing(fn ($state, $record) => $record->sale_price_on_request ? 'Sur devis' : $state)
                    ->sortable(),
                TextColumn::make('total_stock')
                    ->label('Stock')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('rating')
                    ->label('Note')
                    ->placeholder('—')
                    ->formatStateUsing(fn ($state, $record) => $state ? "{$state} ★ ({$record->rating_count})" : '—'),
                IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->before(function (Collection $records) {
                            // Filament already skips individual records whose delete()
                            // throws (see DeleteBulkAction::setUp()) and reports a
                            // generic partial-failure notification — this just adds
                            // the specific reason so the admin knows to deactivate
                            // instead of retrying the delete.
                            $blocked = $records->filter(fn ($record) => $record->reservationItems()->exists());

                            if ($blocked->isNotEmpty()) {
                                Notification::make()
                                    ->warning()
                                    ->title('Certains articles ne pourront pas être supprimés')
                                    ->body('Liés à des réservations existantes : '
                                        .$blocked->pluck('name_fr')->join(', ').'. Désactivez-les plutôt via le champ "Actif".')
                                    ->persistent()
                                    ->send();
                            }
                        }),
                ]),
            ]);
    }
}
