<?php

namespace App\Filament\Resources\Reservations\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Articles réservés';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    /**
     * Line items are created exclusively by ReservationService (under the
     * stock lock) — this manager is read-only so admins can't desync a
     * reservation's items from the availability engine's bookkeeping.
     */
    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('item.name_fr')
                    ->label('Article'),
                TextColumn::make('quantity')
                    ->label('Quantité')
                    ->numeric(),
                TextColumn::make('unit_price_per_day')
                    ->label('Prix unitaire')
                    ->formatStateUsing(fn ($state, $record) => $state ?? $record->unit_sale_price),
                TextColumn::make('subtotal')
                    ->label('Sous-total'),
            ])
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
