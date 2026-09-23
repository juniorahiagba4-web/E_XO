<?php

namespace App\Filament\Resources\Settings\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('key')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->helperText("Clé connue du site : \"homepage_hero_image\" (image de fond de l'accueil)."),
                FileUpload::make('image_upload')
                    ->label('Image')
                    ->image()
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->maxSize(5120)
                    ->directory('settings')
                    ->dehydrated(false)
                    ->live()
                    ->afterStateUpdated(fn ($state, callable $set) => $set('value', $state))
                    ->helperText("Utilisé si cette clé attend le chemin d'une image. Remplit automatiquement le champ Valeur ci-dessous."),
                Textarea::make('value')
                    ->label('Valeur')
                    ->columnSpanFull(),
            ]);
    }
}
