<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->label('Compte sur le site')
                    ->relationship('user', 'name')
                    ->helperText('Vide si le client a demandé un devis sans créer de compte.'),
                TextInput::make('first_name')
                    ->label('Prénom')
                    ->required(),
                TextInput::make('last_name')
                    ->label('Nom')
                    ->required(),
                TextInput::make('email')
                    ->label('Adresse email')
                    ->email()
                    ->helperText('Facultatif pour une commande prise par téléphone.'),
                TextInput::make('phone')
                    ->label('Téléphone')
                    ->tel()
                    ->required(),
            ]);
    }
}
