<?php

namespace App\Http\Resources;

use App\Services\AvailabilityService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class ItemResource extends JsonResource
{
    /** Cached for the lifetime of the request so a collection of items only queries this once. */
    private static ?array $favoriteItemIds = null;

    public function toArray(Request $request): array
    {
        $reviewsLoaded = $this->relationLoaded('reviews');
        $reviewCount = $reviewsLoaded ? $this->reviews->count() : (int) ($this->reviews_count ?? 0);
        $reviewAverage = $reviewCount > 0
            ? round((float) ($reviewsLoaded ? $this->reviews->avg('rating') : $this->reviews_avg_rating), 1)
            : null;

        $user = Auth::guard('sanctum')->user();
        if ($user && self::$favoriteItemIds === null) {
            self::$favoriteItemIds = $user->favoriteItems()->pluck('items.id')->all();
        }

        return [
            'id' => $this->id,
            'category' => $this->whenLoaded('category', fn () => new CategoryResource($this->category)),
            'name' => $this->getLocalized('name'),
            'slug' => $this->slug,
            'description' => $this->getLocalized('description'),
            'specifications' => $this->specifications ?? [],
            'sku' => $this->sku,
            'unit_label' => $this->unit_label,
            'rental_price_per_day' => $this->rental_price_per_day,
            'sale_price' => $this->sale_price,
            'rating' => $reviewAverage ?? $this->rating,
            'rating_count' => $reviewCount > 0 ? $reviewCount : $this->rating_count,
            'reviews' => $reviewsLoaded ? ItemReviewResource::collection($this->reviews) : [],
            'total_stock' => $this->total_stock,
            'purchasable_quantity' => $this->when(
                $this->sale_price !== null,
                fn () => app(AvailabilityService::class)->purchasableQuantity($this->resource),
            ),
            'min_rental_quantity' => $this->min_rental_quantity,
            'image_url' => $this->image_url,
            'gallery' => $this->gallery_urls,
            'is_favorited' => $user ? in_array($this->id, self::$favoriteItemIds, true) : false,
        ];
    }
}
