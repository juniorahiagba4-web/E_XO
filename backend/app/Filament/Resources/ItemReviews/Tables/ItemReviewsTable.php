<?php

namespace App\Filament\Resources\ItemReviews\Tables;

use App\Models\ItemReview;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class ItemReviewsTable
{
    public static function configure(Table $table, bool $showItem = true): Table
    {
        return $table
            ->columns(array_values(array_filter([
                $showItem
                    ? TextColumn::make('item.name_fr')
                        ->label('Article')
                        ->searchable()
                    : null,
                TextColumn::make('author_name')
                    ->label('Client')
                    ->searchable(),
                TextColumn::make('rating')
                    ->label('Note')
                    ->formatStateUsing(fn ($state) => str_repeat('★', (int) round((float) $state)).' ('.(float) $state.')'),
                TextColumn::make('comment')
                    ->label('Commentaire')
                    ->limit(80)
                    ->wrap(),
                IconColumn::make('reservation_id')
                    ->label('Vérifié')
                    ->boolean()
                    ->getStateUsing(fn (ItemReview $record) => $record->reservation_id !== null),
                TextColumn::make('reservation.reference')
                    ->label('Commande')
                    ->fontFamily('mono')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => ItemReview::STATUSES[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('created_at')
                    ->label('Reçu le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])))
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options(ItemReview::STATUSES),
            ])
            ->recordActions([
                static::statusAction('approve', 'Approuver', 'approved', 'success', 'heroicon-o-check-circle'),
                static::statusAction('reject', 'Rejeter', 'rejected', 'danger', 'heroicon-o-x-circle'),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('approve')
                        ->label('Approuver la sélection')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => $records->each->update(['status' => 'approved']))
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    private static function statusAction(string $name, string $label, string $status, string $color, string $icon): Action
    {
        return Action::make($name)
            ->label($label)
            ->icon($icon)
            ->color($color)
            ->visible(fn (ItemReview $record) => $record->status !== $status)
            ->action(fn (ItemReview $record) => $record->update(['status' => $status]));
    }
}
