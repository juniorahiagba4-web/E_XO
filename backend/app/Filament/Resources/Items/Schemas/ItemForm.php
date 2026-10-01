<?php

namespace App\Filament\Resources\Items\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('category_id')
                    ->label('Catégorie')
                    ->relationship('category', 'name_fr')
                    ->searchable()
                    ->required(),

                Section::make('Français')
                    ->columns(1)
                    ->schema([
                        TextInput::make('name_fr')
                            ->label('Nom (FR)')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),
                        Textarea::make('description_fr')->label('Description (FR)'),
                    ]),
                Section::make('English')
                    ->columns(1)
                    ->schema([
                        TextInput::make('name_en')->label('Name (EN)'),
                        Textarea::make('description_en')->label('Description (EN)'),
                    ]),

                TextInput::make('slug')
                    ->required()
                    ->unique(ignoreRecord: true),
                FileUpload::make('image_path')
                    ->label('Photo principale')
                    ->image()
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->maxSize(5120)
                    ->directory('items'),
                FileUpload::make('gallery')
                    ->label('Galerie (autres angles)')
                    ->image()
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->maxSize(5120)
                    ->multiple()
                    ->reorderable()
                    ->directory('items/gallery')
                    ->helperText('Photos supplémentaires affichées comme miniatures sur la fiche article.'),

                TextInput::make('sku')
                    ->label('SKU')
                    ->required()
                    ->unique(ignoreRecord: true),
                TextInput::make('unit_label')
                    ->label('Unité')
                    ->required()
                    ->default('unité'),

                TextInput::make('rental_price_per_day')
                    ->label('Prix de location / jour (XOF)')
                    ->required()
                    ->numeric(),
                Toggle::make('sale_price_on_request')
                    ->label('Prix de vente sur devis uniquement')
                    ->helperText('Active si cet article est à vendre mais sans prix fixe : le site affichera "Prix disponible sur devis" au lieu d\'un montant.')
                    ->live(),
                TextInput::make('sale_price')
                    ->label('Prix de vente (XOF)')
                    ->numeric()
                    ->hidden(fn ($get) => $get('sale_price_on_request'))
                    ->helperText('Laisser vide si cet article n\'est disponible qu\'à la location.'),

                TextInput::make('total_stock')
                    ->label('Stock total')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('min_rental_quantity')
                    ->label('Quantité minimum')
                    ->required()
                    ->numeric()
                    ->default(1),

                Section::make('Caractéristiques')
                    ->schema([
                        Repeater::make('specifications')
                            ->label('')
                            ->schema([
                                TextInput::make('label')->label('Caractéristique')->required(),
                                TextInput::make('value')->label('Valeur')->required(),
                            ])
                            ->columns(2)
                            ->addActionLabel('Ajouter une caractéristique')
                            ->defaultItems(0),
                    ]),

                Section::make('Note par défaut')
                    ->columns(2)
                    ->description('Utilisée tant qu\'aucun avis n\'a été ajouté ci-dessous (onglet Avis, après enregistrement).')
                    ->schema([
                        TextInput::make('rating')
                            ->label('Note moyenne (0 à 5)')
                            ->numeric()
                            ->step(0.1)
                            ->minValue(0)
                            ->maxValue(5),
                        TextInput::make('rating_count')
                            ->label('Nombre d\'avis')
                            ->numeric()
                            ->default(0),
                    ]),

                Toggle::make('is_active')
                    ->label('Actif')
                    ->default(true),
            ]);
    }
}
