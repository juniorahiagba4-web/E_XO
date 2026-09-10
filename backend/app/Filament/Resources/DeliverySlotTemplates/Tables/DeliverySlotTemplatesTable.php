<?php

namespace App\Filament\Resources\DeliverySlotTemplates\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DeliverySlotTemplatesTable
{
    private const WEEKDAYS = [
        0 => 'Dimanche', 1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi',
        4 => 'Jeudi', 5 => 'Vendredi', 6 => 'Samedi',
    ];

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('label')
                    ->label('Libellé')
                    ->searchable(),
                TextColumn::make('weekday')
                    ->label('Jour')
                    ->formatStateUsing(fn (?int $state) => $state === null ? 'Tous les jours' : self::WEEKDAYS[$state]),
                TextColumn::make('start_time')
                    ->label('Début')
                    ->time('H:i')
                    ->sortable(),
                TextColumn::make('end_time')
                    ->label('Fin')
                    ->time('H:i')
                    ->sortable(),
                TextColumn::make('type')
                    ->badge(),
                TextColumn::make('capacity')
                    ->label('Capacité')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),
            ])
            ->filters([])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
