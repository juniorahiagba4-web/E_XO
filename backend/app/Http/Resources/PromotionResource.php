<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PromotionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->getLocalized('title'),
            'description' => $this->getLocalized('description'),
            'image_url' => $this->image_url,
            'discount_type' => $this->discount_type,
            'discount_value' => $this->discount_value,
            'category' => $this->whenLoaded('category', fn () => $this->category ? new CategoryResource($this->category) : null),
            'item' => $this->whenLoaded('item', fn () => $this->item ? new ItemResource($this->item) : null),
            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
        ];
    }
}
