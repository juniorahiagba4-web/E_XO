<?php

namespace App\Filament\Resources\Reservations\Pages;

use App\Filament\Exports\ReservationExporter;
use App\Filament\Resources\Reservations\ReservationResource;
use Filament\Actions\CreateAction;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Actions\ExportAction;
use Filament\Resources\Pages\ListRecords;

class ListReservations extends ListRecords
{
    protected static string $resource = ReservationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExportAction::make()
                ->label('Exporter en CSV')
                ->exporter(ReservationExporter::class)
                ->formats([ExportFormat::Csv]),
            CreateAction::make(),
        ];
    }
}
