<?php

namespace App\Filament\Resources\DeliverySlotTemplates\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class DeliverySlotTemplateForm
{
    private const WEEKDAYS = [
        0 => 'Dimanche', 1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi',
        4 => 'Jeudi', 5 => 'Vendredi', 6 => 'Samedi',
    ];

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('label')
                    ->label('Libellé')
                    ->helperText('Ex: Livraison matin, Retrait après-midi...')
                    ->required(),
                Select::make('weekday')
                    ->label('Jour de la semaine')
                    ->options(self::WEEKDAYS)
                    ->placeholder('Tous les jours')
                    ->native(false),
                TimePicker::make('start_time')
                    ->label('Heure de début')
                    ->required()
                    ->seconds(false),
                TimePicker::make('end_time')
                    ->label('Heure de fin')
                    ->required()
                    ->seconds(false),
                Select::make('type')
                    ->label('Type')
                    ->options([
                        'delivery' => 'Livraison',
                        'pickup' => 'Retrait',
                        'both' => 'Les deux',
                    ])
                    ->required()
                    ->default('both')
                    ->native(false),
                TextInput::make('capacity')
                    ->label('Capacité (réservations simultanées)')
                    ->required()
                    ->numeric()
                    ->default(1),
                Toggle::make('is_active')
                    ->label('Actif')
                    ->default(true),
            ]);
    }
}
