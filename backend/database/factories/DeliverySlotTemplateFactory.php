<?php

namespace Database\Factories;

use App\Models\DeliverySlotTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeliverySlotTemplate>
 */
class DeliverySlotTemplateFactory extends Factory
{
    protected $model = DeliverySlotTemplate::class;

    public function definition(): array
    {
        return [
            'label' => 'Créneau '.$this->faker->time('H:i'),
            'weekday' => null,
            'start_time' => '08:00',
            'end_time' => '12:00',
            'type' => 'both',
            'capacity' => 2,
            'is_active' => true,
        ];
    }
}
