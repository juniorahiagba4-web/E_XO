<?php

namespace App\Filament\Resources\Promotions\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PromotionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Français')
                    ->columns(1)
                    ->schema([
                        TextInput::make('title_fr')
                            ->label('Titre (FR)')
                            ->required(),
                        Textarea::make('description_fr')
                            ->label('Description (FR)'),
                    ]),
                Section::make('English')
                    ->columns(1)
                    ->schema([
                        TextInput::make('title_en')->label('Title (EN)'),
                        Textarea::make('description_en')->label('Description (EN)'),
                    ]),
                FileUpload::make('image_path')
                    ->image()
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->maxSize(5120)
                    ->directory('promotions'),

                Section::make('Remise')
                    ->columns(2)
                    ->schema([
                        Select::make('discount_type')
                            ->label('Type de remise')
                            ->options(['percent' => 'Pourcentage', 'fixed' => 'Montant fixe'])
                            ->required()
                            ->native(false),
                        TextInput::make('discount_value')
                            ->label('Valeur')
                            ->numeric()
                            ->required(),
                        TextInput::make('code')
                            ->label('Code promo')
                            ->unique(ignoreRecord: true)
                            ->alphaDash()
                            ->formatStateUsing(fn (?string $state) => $state ? strtoupper($state) : $state)
                            ->dehydrateStateUsing(fn (?string $state) => $state ? strtoupper($state) : null)
                            ->helperText("Optionnel : si renseigné, le client doit saisir ce code pour bénéficier de la remise. Laisser vide pour une promotion automatique."),
                        Select::make('category_id')
                            ->label('Catégorie concernée')
                            ->relationship('category', 'name_fr')
                            ->searchable()
                            ->helperText('Optionnel : laisser vide si la promotion cible un article précis ou l\'ensemble du catalogue.'),
                        Select::make('item_id')
                            ->label('Article concerné')
                            ->relationship('item', 'name_fr')
                            ->searchable()
                            ->helperText('Optionnel : laisser vide si la promotion cible une catégorie ou l\'ensemble du catalogue.'),
                    ]),

                Section::make('Période')
                    ->columns(2)
                    ->schema([
                        DateTimePicker::make('starts_at')
                            ->label('Début')
                            ->native(false),
                        DateTimePicker::make('ends_at')
                            ->label('Fin')
                            ->native(false),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ]),
            ]);
    }
}
