<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Item;
use App\Models\Promotion;
use Illuminate\Database\Seeder;

class PromotionSeeder extends Seeder
{
    public function run(): void
    {
        $chairs = Category::query()->where('slug', 'chaises')->first();
        $cooler = Item::query()->where('slug', 'glaciere-100l')->first();

        if ($chairs) {
            Promotion::query()->firstOrCreate(
                ['title_fr' => 'Saison des mariages'],
                [
                    'title_en' => 'Wedding season',
                    'description_fr' => '10% de réduction sur toutes les chaises pour les événements de plus de 100 invités.',
                    'description_en' => '10% off all chairs for events with more than 100 guests.',
                    'discount_type' => 'percent',
                    'discount_value' => 10,
                    'category_id' => $chairs->id,
                    'starts_at' => now()->subDays(3),
                    'ends_at' => now()->addMonths(2),
                    'is_active' => true,
                ],
            );
        }

        if ($cooler) {
            Promotion::query()->firstOrCreate(
                ['title_fr' => 'Achetez votre glacière'],
                [
                    'title_en' => 'Buy your cooler',
                    'description_fr' => '5 000 XOF de réduction immédiate sur l\'achat d\'une glacière 100L.',
                    'description_en' => '5,000 XOF off buying a 100L cooler.',
                    'discount_type' => 'fixed',
                    'discount_value' => 5000,
                    'item_id' => $cooler->id,
                    'starts_at' => now(),
                    'ends_at' => now()->addMonth(),
                    'is_active' => true,
                ],
            );
        }

        Promotion::query()->firstOrCreate(
            ['title_fr' => 'Livraison offerte à Lomé'],
            [
                'title_en' => 'Free delivery in Lomé',
                'description_fr' => 'Livraison gratuite pour toute commande de plus de 50 000 XOF dans Lomé.',
                'description_en' => 'Free delivery on any order over 50,000 XOF within Lomé.',
                'discount_type' => 'fixed',
                'discount_value' => 0,
                'starts_at' => now()->subWeek(),
                'ends_at' => now()->addMonths(3),
                'is_active' => true,
            ],
        );
    }
}
