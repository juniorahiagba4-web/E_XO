<?php

namespace App\Filament\Resources\Items\Pages;

use App\Filament\Resources\Items\ItemResource;
use App\Services\TranslationService;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditItem extends EditRecord
{
    protected static string $resource = ItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->before(function ($record) {
                    if ($record->reservationItems()->exists()) {
                        Notification::make()
                            ->danger()
                            ->title('Suppression impossible')
                            ->body('Cet article est lié à au moins une réservation existante et ne peut pas être supprimé (l\'historique des commandes doit rester intact). Désactivez-le plutôt via le champ "Actif".')
                            ->persistent()
                            ->send();

                        $this->halt();
                    }
                }),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! empty($data['sale_price_on_request'])) {
            $data['sale_price'] = null;
        }

        return app(TranslationService::class)->fillMissingItemTranslations($data);
    }
}
