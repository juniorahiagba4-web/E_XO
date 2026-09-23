<?php

namespace App\Filament\Exports;

use App\Models\Customer;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Str;

class CustomerExporter extends Exporter
{
    protected static ?string $model = Customer::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('first_name')
                ->label('Prénom'),
            ExportColumn::make('last_name')
                ->label('Nom'),
            ExportColumn::make('email')
                ->label('Email'),
            ExportColumn::make('phone')
                ->label('Téléphone'),
            ExportColumn::make('company_name')
                ->label('Entreprise'),
            ExportColumn::make('city')
                ->label('Ville'),
            ExportColumn::make('created_at')
                ->label('Client depuis'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Votre export des clients est terminé : '
            . $export->successful_rows . ' ' . Str::plural('ligne', $export->successful_rows) . ' exportée(s).';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . $failedRowsCount . ' ' . Str::plural('ligne', $failedRowsCount) . ' en échec.';
        }

        return $body;
    }
}
