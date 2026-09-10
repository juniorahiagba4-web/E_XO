<?php

namespace App\Filament\Resources\DeliverySlotTemplates\Pages;

use App\Filament\Resources\DeliverySlotTemplates\DeliverySlotTemplateResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDeliverySlotTemplate extends EditRecord
{
    protected static string $resource = DeliverySlotTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
