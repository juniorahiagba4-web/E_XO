<?php

namespace App\Filament\Resources\Items\Schemas;

use Filament\Forms\Components\FileUpload;
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
                    ->label('Photo')
                    ->image()
                    ->directory('items'),

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
                TextInput::make('deposit_amount')
                    ->label('Caution (XOF)')
                    ->numeric(),
                TextInput::make('sale_price')
                    ->label('Prix de vente (XOF, phase 2)')
                    ->numeric(),

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

                Toggle::make('is_active')
                    ->label('Actif')
                    ->default(true),
            ]);
    }
}
