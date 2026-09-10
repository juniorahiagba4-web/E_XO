<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\Reservation;
use App\Models\ReservationItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReservationItem>
 */
class ReservationItemFactory extends Factory
{
    protected $model = ReservationItem::class;

    public function definition(): array
    {
        return [
            'reservation_id' => Reservation::factory(),
            'item_id' => Item::factory(),
            'quantity' => $this->faker->numberBetween(1, 5),
            'unit_price_per_day' => $this->faker->numberBetween(500, 5000),
            'subtotal' => 0,
        ];
    }
}
