<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedFields;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'category_id', 'name_fr', 'name_en', 'slug', 'description_fr', 'description_en',
    'image_path', 'sku', 'unit_label',
    'rental_price_per_day', 'deposit_amount', 'sale_price',
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
            'total_stock' => 'integer',
            'min_rental_quantity' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? asset('storage/'.$this->image_path) : null;
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function reservationItems(): HasMany
    {
        return $this->hasMany(ReservationItem::class);
    }
}
