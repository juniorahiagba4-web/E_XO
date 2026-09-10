<?php

namespace Database\Seeders;

use App\Models\DeliverySlotTemplate;
use Illuminate\Database\Seeder;

class DeliverySlotTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $slots = [
            ['label' => 'Matin (8h - 12h)', 'start_time' => '08:00', 'end_time' => '12:00', 'type' => 'both', 'capacity' => 3],
            ['label' => 'Après-midi (13h - 17h)', 'start_time' => '13:00', 'end_time' => '17:00', 'type' => 'both', 'capacity' => 3],
            ['label' => 'Soir (17h - 20h)', 'start_time' => '17:00', 'end_time' => '20:00', 'type' => 'delivery', 'capacity' => 2],
        ];

        foreach ($slots as $slot) {
            DeliverySlotTemplate::query()->firstOrCreate(
                ['label' => $slot['label']],
                [
                    'weekday' => null,
                    'start_time' => $slot['start_time'],
                    'end_time' => $slot['end_time'],
                    'type' => $slot['type'],
                    'capacity' => $slot['capacity'],
                    'is_active' => true,
                ],
            );
        }
    }
}
