<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['item_id', 'reservation_id', 'customer_id', 'author_name', 'rating', 'comment', 'status'])]
class ItemReview extends Model
{
    use HasFactory;

    /**
     * pending: submitted by a customer, waiting for moderation.
     * approved: shown on the site.
     * rejected: refused by an admin.
     * archived: entered by hand before customer reviews existed, never shown.
     */
    public const STATUSES = [
        'pending' => 'En attente',
        'approved' => 'Approuvé',
        'rejected' => 'Rejeté',
        'archived' => 'Archivé',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'decimal:1',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }
}
