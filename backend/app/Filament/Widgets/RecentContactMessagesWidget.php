<?php

namespace App\Filament\Widgets;

use App\Models\ContactMessage;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentContactMessagesWidget extends TableWidget
{
    protected static ?string $heading = 'Messages de contact non lus';

    protected static ?int $sort = 5;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                fn (): Builder => ContactMessage::query()
                    ->where('status', ContactMessage::STATUS_NEW)
                    ->latest()
                    ->limit(5)
            )
            ->columns([
                TextColumn::make('name')->label('Nom'),
                TextColumn::make('subject')->label('Sujet')->placeholder('—'),
                TextColumn::make('created_at')->label('Reçu le')->dateTime('d/m/Y H:i'),
            ])
            ->paginated(false);
    }
}
