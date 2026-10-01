<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedFields;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'category_id', 'name_fr', 'name_en', 'slug', 'description_fr', 'description_en',
    'image_path', 'gallery', 'specifications', 'sku', 'unit_label',
    'rental_price_per_day', 'deposit_amount', 'sale_price', 'sale_price_on_request',
    'rating', 'rating_count',
    'total_stock', 'min_rental_quantity', 'is_active',
])]
class Item extends Model
{
    use HasFactory, HasLocalizedFields;

    protected function casts(): array
    {
        return [
            'rental_price_per_day' => 'decimal:2',
            'deposit_amount' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'sale_price_on_request' => 'boolean',
            'rating' => 'decimal:1',
            'rating_count' => 'integer',
            'total_stock' => 'integer',
            'min_rental_quantity' => 'integer',
            'is_active' => 'boolean',
            'gallery' => 'array',
            'specifications' => 'array',
        ];
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? asset('storage/'.$this->image_path) : null;
    }

    public function getGalleryUrlsAttribute(): array
    {
        return collect($this->gallery ?? [])
            ->map(fn (string $path) => asset('storage/'.$path))
            ->values()
            ->all();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function reservationItems(): HasMany
    {
        return $this->hasMany(ReservationItem::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ItemReview::class);
    }

    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorites')->withTimestamps();
    }
}
