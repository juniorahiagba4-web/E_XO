<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndAdminUserSeeder::class,
            CatalogueSeeder::class,
            DeliverySlotTemplateSeeder::class,
            PromotionSeeder::class,
            ItemReviewSeeder::class,
        ]);
    }
}
