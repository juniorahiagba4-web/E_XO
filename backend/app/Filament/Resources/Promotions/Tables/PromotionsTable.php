<?php

namespace App\Filament\Resources\Promotions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class PromotionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image_path')->label(''),
                TextColumn::make('title_fr')
                    ->label('Titre')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('discount_value')
                    ->label('Remise')
                    ->formatStateUsing(fn ($state, $record) => $record->discount_type === 'percent'
                        ? "-{$state}%"
                        : '-'.number_format((float) $state, 0, ',', ' ').' XOF'),
                TextColumn::make('code')
                    ->label('Code')
                    ->badge()
                    ->placeholder('Automatique')
                    ->searchable(),
                TextColumn::make('category.name_fr')
                    ->label('Catégorie')
                    ->placeholder('—'),
                TextColumn::make('item.name_fr')
                    ->label('Article')
                    ->placeholder('—'),
                TextColumn::make('starts_at')
                    ->label('Début')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->label('Fin')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
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
