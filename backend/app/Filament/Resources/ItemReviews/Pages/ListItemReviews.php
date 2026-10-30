<?php

namespace App\Filament\Resources\ItemReviews\Pages;

use App\Filament\Resources\ItemReviews\ItemReviewResource;
use Filament\Resources\Pages\ListRecords;

class ListItemReviews extends ListRecords
{
    protected static string $resource = ItemReviewResource::class;
}
