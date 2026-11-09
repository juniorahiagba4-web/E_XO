<?php

namespace App\Filament\Exports;

use App\Models\Reservation;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Str;

class ReservationExporter extends Exporter
{
    protected static ?string $model = Reservation::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('reference')
                ->label('Référence'),
            ExportColumn::make('type')
                ->label('Type')
                ->formatStateUsing(fn (?string $state) => Reservation::TYPE_LABELS[$state] ?? $state),
            ExportColumn::make('status')
                ->label('Statut')
                ->formatStateUsing(fn (?string $state) => Reservation::STATUS_LABELS[$state] ?? $state),
            ExportColumn::make('customer.full_name')
                ->label('Client'),
            ExportColumn::make('customer.email')
                ->label('Email client'),
            ExportColumn::make('customer.phone')
                ->label('Téléphone client'),
            ExportColumn::make('event_start_date')
                ->label('Début événement'),
            ExportColumn::make('event_end_date')
                ->label('Fin événement'),
            ExportColumn::make('purchase_date')
                ->label("Date d'achat"),
            ExportColumn::make('delivery_method')
                ->label('Livraison')
                ->formatStateUsing(fn (?string $state) => Reservation::DELIVERY_METHOD_LABELS[$state] ?? $state),
            ExportColumn::make('delivery_date')
                ->label('Date de livraison'),
            ExportColumn::make('delivery_time')
                ->label('Heure de livraison'),
            ExportColumn::make('return_date')
                ->label('Date de retour'),
            ExportColumn::make('return_time')
                ->label('Heure de retour'),
            ExportColumn::make('delivery_address')
                ->label('Adresse de livraison'),
            ExportColumn::make('subtotal')
                ->label('Sous-total'),
            ExportColumn::make('discount')
                ->label('Remise'),
            ExportColumn::make('total')
                ->label('Total'),
            ExportColumn::make('confirmed_at')
                ->label('Confirmée le'),
            ExportColumn::make('cancelled_at')
                ->label('Annulée le'),
            ExportColumn::make('created_at')
                ->label('Créée le'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Votre export des réservations est terminé : '
            .$export->successful_rows.' '.Str::plural('ligne', $export->successful_rows).' exportée(s).';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.$failedRowsCount.' '.Str::plural('ligne', $failedRowsCount).' en échec.';
        }

        return $body;
    }
}
