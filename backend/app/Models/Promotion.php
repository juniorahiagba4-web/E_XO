<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedFields;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

#[Fillable([
    'title_fr', 'title_en', 'description_fr', 'description_en', 'image_path',
    'discount_type', 'discount_value', 'code', 'category_id', 'item_id',
    'starts_at', 'ends_at', 'is_active',
])]
class Promotion extends Model
{
    use HasFactory, HasLocalizedFields;

    protected function casts(): array
    {
        return [
            'discount_value' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
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

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function scopeCurrentlyActive(Builder $query): Builder
    {
        $now = Carbon::now();

        return $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now));
    }

    public function scopeForCode(Builder $query, string $code): Builder
    {
        return $query->whereRaw('LOWER(code) = ?', [strtolower($code)]);
    }

    /**
     * Whether this promotion's scope (specific item, category, or global)
     * covers the given item.
     */
    public function appliesToItem(Item $item): bool
    {
        if ($this->item_id) {
            return $this->item_id === $item->id;
        }

        if ($this->category_id) {
            return $this->category_id === $item->category_id;
        }

        return true;
    }
}
