<?php

namespace App\Filament\Widgets;

use App\Models\Item;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class TopItemsWidget extends TableWidget
{
    protected static ?string $heading = 'Articles les plus réservés';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 1;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                fn (): Builder => Item::query()
                    ->withSum('reservationItems as total_reserved', 'quantity')
                    ->orderByDesc('total_reserved')
                    ->limit(5)
            )
            ->columns([
                TextColumn::make('name_fr')->label('Article'),
                TextColumn::make('category.name_fr')->label('Catégorie')->placeholder('—'),
                TextColumn::make('total_reserved')->label('Qté réservée')->numeric()->placeholder('0'),
            ])
            ->paginated(false);
    }
}
