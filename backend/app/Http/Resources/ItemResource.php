<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category' => $this->whenLoaded('category', fn () => new CategoryResource($this->category)),
            'name' => $this->getLocalized('name'),
            'slug' => $this->slug,
            'description' => $this->getLocalized('description'),
            'sku' => $this->sku,
            'unit_label' => $this->unit_label,
            'rental_price_per_day' => $this->rental_price_per_day,
            'deposit_amount' => $this->deposit_amount,
            'total_stock' => $this->total_stock,
            'min_rental_quantity' => $this->min_rental_quantity,
            'image_url' => $this->image_url,
        ];
    }
}
