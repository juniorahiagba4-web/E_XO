<?php

namespace App\Filament\Resources\ItemReviews;

use App\Filament\Resources\ItemReviews\Pages\ListItemReviews;
use App\Filament\Resources\ItemReviews\Tables\ItemReviewsTable;
use App\Models\ItemReview;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ItemReviewResource extends Resource
{
    protected static ?string $model = ItemReview::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;

    protected static string|\UnitEnum|null $navigationGroup = 'Catalogue';

    protected static ?string $modelLabel = 'Avis client';

    protected static ?string $pluralModelLabel = 'Avis clients';

    public static function table(Table $table): Table
    {
        return ItemReviewsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListItemReviews::route('/'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('status', 'pending')->count();

        return $count > 0 ? (string) $count : null;
    }
}
