<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Reservation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reservation>
 */
class ReservationFactory extends Factory
{
    protected $model = Reservation::class;

    public function definition(): array
    {
        $start = $this->faker->dateTimeBetween('+1 day', '+10 days');
        $end = (clone $start)->modify('+2 days');

        return [
            'customer_id' => Customer::factory(),
            'status' => 'quote_sent',
            'event_start_date' => $start,
            'event_end_date' => $end,
            'delivery_method' => 'pickup',
            'subtotal' => 0,
            'deposit_required' => 0,
            'discount' => 0,
            'total' => 0,
            'currency' => 'XOF',
            'hold_expires_at' => now()->addHours(Reservation::QUOTE_HOLD_HOURS),
        ];
    }

    public function confirmed(): static
    {
        return $this->state(['status' => 'confirmed']);
    }

    public function cancelled(): static
    {
        return $this->state(['status' => 'cancelled']);
    }
}
