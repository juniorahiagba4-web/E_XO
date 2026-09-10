<?php

namespace App\Filament\Resources\DeliverySlotTemplates\Pages;

use App\Filament\Resources\DeliverySlotTemplates\DeliverySlotTemplateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDeliverySlotTemplates extends ListRecords
{
    protected static string $resource = DeliverySlotTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
