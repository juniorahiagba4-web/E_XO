<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ReservationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'reference' => $this->reference,
            'status' => $this->status,
            'event_start_date' => $this->event_start_date->toDateString(),
            'event_end_date' => $this->event_end_date->toDateString(),
            'delivery_method' => $this->delivery_method,
            'subtotal' => $this->subtotal,
            'deposit_required' => $this->deposit_required,
            'total' => $this->total,
            'currency' => $this->currency,
            'quote_pdf_url' => $this->quote_pdf_path ? Storage::disk('public')->url($this->quote_pdf_path) : null,
            'whatsapp_message' => $this->whatsappMessage(),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($line) => [
                'item_name' => $line->item?->getLocalized('name'),
                'quantity' => $line->quantity,
                'unit_price_per_day' => $line->unit_price_per_day,
                'subtotal' => $line->subtotal,
            ])),
        ];
    }
}
