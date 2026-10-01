<?php

namespace App\Filament\Resources\Items\Pages;

use App\Filament\Resources\Items\ItemResource;
use App\Services\TranslationService;
use Filament\Resources\Pages\CreateRecord;

class CreateItem extends CreateRecord
{
    protected static string $resource = ItemResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (! empty($data['sale_price_on_request'])) {
            $data['sale_price'] = null;
        }

        return app(TranslationService::class)->fillMissingItemTranslations($data);
    }
}
