<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Item;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CatalogueSeeder extends Seeder
{
    public function run(): void
    {
        $catalogue = [
            'Chaises' => [
                'name_en' => 'Chairs',
                'items' => [
                    ['fr' => 'Chaise Chiavari dorée', 'en' => 'Gold Chiavari chair', 'price' => 750, 'deposit' => 1500, 'stock' => 300],
                    ['fr' => 'Chaise pliante blanche', 'en' => 'White folding chair', 'price' => 500, 'deposit' => 1000, 'stock' => 400],
                ],
            ],
            'Tables' => [
                'name_en' => 'Tables',
                'items' => [
                    ['fr' => 'Table ronde 10 personnes', 'en' => 'Round table (10 seats)', 'price' => 3000, 'deposit' => 5000, 'stock' => 40],
                    ['fr' => 'Table rectangulaire 8 personnes', 'en' => 'Rectangular table (8 seats)', 'price' => 2500, 'deposit' => 4000, 'stock' => 40],
                ],
            ],
            'Nappes & décoration' => [
                'name_en' => 'Tablecloths & decor',
                'items' => [
                    ['fr' => 'Nappe ronde satinée', 'en' => 'Satin round tablecloth', 'price' => 1000, 'deposit' => 1000, 'stock' => 60],
                    ['fr' => 'Chemin de table doré', 'en' => 'Gold table runner', 'price' => 500, 'deposit' => 500, 'stock' => 60],
                ],
            ],
            'Glacières' => [
                'name_en' => 'Coolers',
                'items' => [
                    ['fr' => 'Glacière 100L', 'en' => '100L cooler', 'price' => 5000, 'deposit' => 10000, 'stock' => 15],
                    ['fr' => 'Glacière 50L', 'en' => '50L cooler', 'price' => 3000, 'deposit' => 6000, 'stock' => 20],
                ],
            ],
        ];

        $sortOrder = 0;

        foreach ($catalogue as $nameFr => $data) {
            $category = Category::query()->firstOrCreate(
                ['slug' => Str::slug($nameFr)],
                [
                    'name_fr' => $nameFr,
                    'name_en' => $data['name_en'],
                    'sort_order' => $sortOrder++,
                    'is_active' => true,
                ],
            );

            foreach ($data['items'] as $item) {
                Item::query()->firstOrCreate(
                    ['sku' => 'SKU-'.Str::upper(Str::slug($item['fr'], ''))],
                    [
                        'category_id' => $category->id,
                        'name_fr' => $item['fr'],
                        'name_en' => $item['en'],
                        'slug' => Str::slug($item['fr']),
                        'unit_label' => 'unité',
                        'rental_price_per_day' => $item['price'],
                        'deposit_amount' => $item['deposit'],
                        'total_stock' => $item['stock'],
                        'min_rental_quantity' => 1,
                        'is_active' => true,
                    ],
                );
            }
        }
    }
}
