<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nom')
                    ->required(),
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true),
                TextInput::make('password')
                    ->label('Mot de passe')
                    ->password()
                    ->revealable()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                    ->helperText('Laisser vide pour conserver le mot de passe actuel.'),
                Select::make('roles')
                    ->label('Rôle')
                    ->relationship('roles', 'name')
                    ->getOptionLabelFromRecordUsing(fn ($record): string => match ($record->name) {
                        'admin' => 'Administrateur',
                        'manager' => 'Gestionnaire',
                        default => $record->name,
                    })
                    ->required()
                    ->preload()
                    ->native(false)
                    ->helperText('Administrateur : accès complet, y compris la gestion des comptes et la suppression. Gestionnaire : accès au catalogue, réservations et clients, sans suppression ni gestion des comptes.'),
                Toggle::make('is_active')
                    ->label('Compte actif')
                    ->default(true)
                    ->helperText('Un compte désactivé ne peut plus se connecter au panneau.'),
            ]);
    }
}
