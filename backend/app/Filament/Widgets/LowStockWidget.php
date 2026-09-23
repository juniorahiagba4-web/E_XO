<?php

namespace App\Filament\Widgets;

use App\Models\Item;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class LowStockWidget extends TableWidget
{
    protected static ?string $heading = 'Stock bas';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 1;

    private const THRESHOLD = 15;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                fn (): Builder => Item::query()
                    ->where('is_active', true)
                    ->where('total_stock', '<', self::THRESHOLD)
                    ->orderBy('total_stock')
                    ->limit(5)
            )
            ->columns([
                TextColumn::make('name_fr')->label('Article'),
                TextColumn::make('total_stock')
                    ->label('Stock restant')
                    ->badge()
                    ->color('danger'),
            ])
            ->paginated(false);
    }
}
