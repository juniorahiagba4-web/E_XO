<?php

namespace App\Filament\Resources\Reservations\Pages;

use App\Filament\Resources\Reservations\ReservationResource;
use App\Models\Reservation;
use App\Services\QuotePdfService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;

class EditReservation extends EditRecord
{
    protected static string $resource = ReservationResource::class;

    public function getTitle(): string
    {
        $record = $this->getRecord();

        return ($record->type === 'purchase' ? 'Achat ' : 'Location ').$record->reference;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('quote_pdf')
                ->label(fn (Reservation $record) => $record->type === 'purchase' ? 'Bon de commande PDF' : 'Devis PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->url(fn (Reservation $record) => $record->quote_pdf_path ? Storage::disk('public')->url($record->quote_pdf_path) : null)
                ->openUrlInNewTab()
                ->visible(fn (Reservation $record) => filled($record->quote_pdf_path)),
            DeleteAction::make(),
        ];
    }

    /**
     * Dates the team does not type in twice: a rental is delivered on its
     * first day and collected on its last; a purchase has no period.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $record = $this->getRecord();
        $delivered = ($data['delivery_method'] ?? $record->delivery_method) === 'delivery';

        if ($record->type === 'rental') {
            $data['delivery_date'] = $data['event_start_date'] ?? null;
            $data['return_date'] = $data['event_end_date'] ?? null;
            $data['purchase_date'] = null;
        } else {
            $data['event_start_date'] = null;
            $data['event_end_date'] = null;
            $data['return_date'] = null;
            $data['return_time'] = null;
        }

        if (! $delivered) {
            $data['delivery_address'] = null;
            $data['delivery_time'] = null;
            $data['return_time'] = null;
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $record = $this->getRecord();
        $record->recalculateTotals();
        app(QuotePdfService::class)->refresh($record);
        $record->refresh();

        $this->dispatch('reservation-saved');
    }

    /** A price was typed in "Articles réservés": show the new amounts. */
    #[On('reservation-lines-updated')]
    public function refreshAmounts(): void
    {
        $this->getRecord()->refresh();
    }
}
