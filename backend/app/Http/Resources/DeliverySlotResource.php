<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliverySlotResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource['template']->id,
            'label' => $this->resource['template']->label,
            'date' => $this->resource['date'],
            'start_time' => $this->resource['template']->start_time,
            'end_time' => $this->resource['template']->end_time,
            'type' => $this->resource['template']->type,
            'remaining_capacity' => $this->resource['remaining_capacity'],
        ];
    }
}
