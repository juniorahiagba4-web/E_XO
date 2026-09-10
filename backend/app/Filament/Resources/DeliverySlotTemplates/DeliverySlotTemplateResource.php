<?php

namespace App\Filament\Resources\DeliverySlotTemplates;

use App\Filament\Resources\DeliverySlotTemplates\Pages\CreateDeliverySlotTemplate;
use App\Filament\Resources\DeliverySlotTemplates\Pages\EditDeliverySlotTemplate;
use App\Filament\Resources\DeliverySlotTemplates\Pages\ListDeliverySlotTemplates;
use App\Filament\Resources\DeliverySlotTemplates\Schemas\DeliverySlotTemplateForm;
use App\Filament\Resources\DeliverySlotTemplates\Tables\DeliverySlotTemplatesTable;
use App\Models\DeliverySlotTemplate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DeliverySlotTemplateResource extends Resource
{
    protected static ?string $model = DeliverySlotTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static string|\UnitEnum|null $navigationGroup = 'Réservations';

    protected static ?string $navigationLabel = 'Créneaux de livraison';

    public static function form(Schema $schema): Schema
    {
        return DeliverySlotTemplateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DeliverySlotTemplatesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDeliverySlotTemplates::route('/'),
            'create' => CreateDeliverySlotTemplate::route('/create'),
            'edit' => EditDeliverySlotTemplate::route('/{record}/edit'),
        ];
    }
}
