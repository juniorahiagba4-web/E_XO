<?php

namespace App\Filament\Resources\Items\RelationManagers;

use App\Filament\Resources\ItemReviews\Tables\ItemReviewsTable;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

/**
 * Reviews come only from customers, through the link sent after a completed
 * order, so this tab moderates them but cannot create or edit any.
 */
class ReviewsRelationManager extends RelationManager
{
    protected static string $relationship = 'reviews';

    protected static ?string $title = 'Avis';

    public function table(Table $table): Table
    {
        return ItemReviewsTable::configure($table, showItem: false)
            ->recordTitleAttribute('author_name');
    }
}
