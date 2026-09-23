<?php

namespace App\Filament\Resources\Items\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ReviewsRelationManager extends RelationManager
{
    protected static string $relationship = 'reviews';

    protected static ?string $title = 'Avis';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('author_name')
                ->label('Auteur')
                ->required()
                ->maxLength(150),
            TextInput::make('rating')
                ->label('Note (0 à 5)')
                ->required()
                ->numeric()
                ->step(0.1)
                ->minValue(0)
                ->maxValue(5),
            TextInput::make('comment')
                ->label('Commentaire')
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('author_name')
            ->columns([
                TextColumn::make('author_name')->label('Auteur'),
                TextColumn::make('rating')->label('Note')->formatStateUsing(fn ($state) => "{$state} ★"),
                TextColumn::make('comment')->label('Commentaire')->limit(60),
                TextColumn::make('created_at')->label('Ajouté le')->dateTime('d/m/Y'),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
