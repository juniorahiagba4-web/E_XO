<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Français')
                    ->columns(1)
                    ->schema([
                        TextInput::make('name_fr')
                            ->label('Nom (FR)')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),
                        Textarea::make('description_fr')
                            ->label('Description (FR)'),
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
                    ->image()
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->maxSize(5120)
                    ->directory('categories'),
                Select::make('parent_id')
                    ->label('Catégorie parente')
                    ->relationship('parent', 'name_fr')
                    ->searchable(),
                TextInput::make('sort_order')
                    ->numeric()
                    ->default(0),
                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
            ]);
    }
}
