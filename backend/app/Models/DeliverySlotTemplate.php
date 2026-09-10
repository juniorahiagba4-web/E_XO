<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['label', 'weekday', 'start_time', 'end_time', 'type', 'capacity', 'is_active'])]
class DeliverySlotTemplate extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'weekday' => 'integer',
            'capacity' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function appliesToWeekday(int $weekday): bool
    {
        return $this->weekday === null || $this->weekday === $weekday;
    }

    public function matchesType(string $type): bool
    {
        return $this->type === 'both' || $this->type === $type;
    }
}
