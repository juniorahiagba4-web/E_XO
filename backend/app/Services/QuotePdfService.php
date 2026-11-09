<?php

namespace App\Services;

use App\Models\Reservation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class QuotePdfService
{
    public function generate(Reservation $reservation): string
    {
        $reservation->loadMissing(['customer', 'items.item']);

        $pdf = Pdf::loadView('pdf.quote', ['reservation' => $reservation]);

        $path = "quotes/{$reservation->reference}.pdf";

        Storage::disk('public')->put($path, $pdf->output());

        return $path;
    }

    /** Rebuilds the PDF after the order was changed in the admin. */
    public function refresh(Reservation $reservation): void
    {
        $reservation->unsetRelation('items');
        $path = $this->generate($reservation);
        $reservation->forceFill(['quote_pdf_path' => $path, 'quote_generated_at' => now()])->saveQuietly();
    }
}
