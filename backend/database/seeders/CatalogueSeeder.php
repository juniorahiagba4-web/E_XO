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
                    [
                        'fr' => 'Chaise Chiavari dorée', 'en' => 'Gold Chiavari chair', 'price' => 750, 'deposit' => 1500, 'sale' => 15000, 'stock' => 300, 'rating' => 4.8, 'rating_count' => 96,
                        'description_fr' => "Chaise Chiavari élégante au cadre doré, idéale pour les mariages et réceptions haut de gamme. Empilable pour un stockage facile.",
                        'description_en' => 'Elegant gold-framed Chiavari chair, ideal for weddings and upscale receptions. Stackable for easy storage.',
                        'specifications' => [
                            ['label' => 'Matériau', 'value' => 'Résine et métal doré'],
                            ['label' => 'Dimensions', 'value' => '40 x 40 x 92 cm'],
                            ['label' => 'Poids', 'value' => '3.5 kg'],
                            ['label' => 'Capacité de charge', 'value' => '120 kg'],
                        ],
                    ],
                    [
                        'fr' => 'Chaise pliante blanche', 'en' => 'White folding chair', 'price' => 500, 'deposit' => 1000, 'sale' => 9000, 'stock' => 400, 'rating' => 4.5, 'rating_count' => 142,
                        'description_fr' => 'Chaise pliante robuste et légère, parfaite pour les événements en extérieur ou les grands rassemblements.',
                        'description_en' => 'Sturdy, lightweight folding chair, perfect for outdoor events or large gatherings.',
                        'specifications' => [
                            ['label' => 'Matériau', 'value' => 'Métal et polypropylène'],
                            ['label' => 'Dimensions', 'value' => '45 x 45 x 88 cm'],
                            ['label' => 'Poids', 'value' => '2.8 kg'],
                        ],
                    ],
                ],
            ],
            'Tables' => [
                'name_en' => 'Tables',
                'items' => [
                    [
                        'fr' => 'Table ronde 10 personnes', 'en' => 'Round table (10 seats)', 'price' => 3000, 'deposit' => 5000, 'sale' => 65000, 'stock' => 40, 'rating' => 4.6, 'rating_count' => 38,
                        'description_fr' => "Grande table ronde pliante, idéale pour les repas de mariage ou banquets jusqu'à 10 convives.",
                        'description_en' => 'Large round folding table, ideal for wedding dinners or banquets seating up to 10 guests.',
                        'specifications' => [
                            ['label' => 'Diamètre', 'value' => '180 cm'],
                            ['label' => 'Hauteur', 'value' => '75 cm'],
                            ['label' => 'Capacité', 'value' => '10 personnes'],
                        ],
                    ],
                    [
                        'fr' => 'Table rectangulaire 8 personnes', 'en' => 'Rectangular table (8 seats)', 'price' => 2500, 'deposit' => 4000, 'sale' => null, 'stock' => 40, 'rating' => 4.4, 'rating_count' => 27,
                        'description_fr' => 'Table rectangulaire pliante polyvalente, adaptée aux buffets, séminaires et repas assis.',
                        'description_en' => 'Versatile rectangular folding table, suited to buffets, seminars and seated meals.',
                        'specifications' => [
                            ['label' => 'Dimensions', 'value' => '180 x 90 cm'],
                            ['label' => 'Hauteur', 'value' => '75 cm'],
                            ['label' => 'Capacité', 'value' => '8 personnes'],
                        ],
                    ],
                ],
            ],
            'Nappes & décoration' => [
                'name_en' => 'Tablecloths & decor',
                'items' => [
                    [
                        'fr' => 'Nappe ronde satinée', 'en' => 'Satin round tablecloth', 'price' => 1000, 'deposit' => 1000, 'sale' => 8000, 'stock' => 60, 'rating' => 4.7, 'rating_count' => 54,
                        'description_fr' => 'Nappe satinée haut de gamme qui habille élégamment vos tables rondes.',
                        'description_en' => 'Premium satin tablecloth that elegantly dresses your round tables.',
                        'specifications' => [
                            ['label' => 'Matière', 'value' => 'Satin polyester'],
                            ['label' => 'Diamètre', 'value' => '280 cm'],
                            ['label' => 'Entretien', 'value' => 'Lavable en machine'],
                        ],
                    ],
                    [
                        'fr' => 'Chemin de table doré', 'en' => 'Gold table runner', 'price' => 500, 'deposit' => 500, 'sale' => 3500, 'stock' => 60, 'rating' => 4.3, 'rating_count' => 19,
                        'description_fr' => 'Chemin de table doré scintillant pour sublimer votre décoration de table.',
                        'description_en' => 'Sparkling gold table runner to elevate your table decor.',
                        'specifications' => [
                            ['label' => 'Matière', 'value' => 'Sequins sur voile'],
                            ['label' => 'Dimensions', 'value' => '30 x 275 cm'],
                        ],
                    ],
                ],
            ],
            'Glacières' => [
                'name_en' => 'Coolers',
                'items' => [
                    [
                        'fr' => 'Glacière 100L', 'en' => '100L cooler', 'price' => 5000, 'deposit' => 10000, 'sale' => 85000, 'stock' => 15, 'rating' => 4.9, 'rating_count' => 22,
                        'description_fr' => "Grande glacière de 100 litres, parfaite pour garder boissons et rafraîchissements au frais toute la durée de l'événement.",
                        'description_en' => "Large 100-liter cooler, perfect for keeping drinks and refreshments cold throughout the event.",
                        'specifications' => [
                            ['label' => 'Capacité', 'value' => '100 L'],
                            ['label' => 'Dimensions', 'value' => '75 x 45 x 45 cm'],
                            ['label' => 'Autonomie glace', 'value' => "Jusqu'à 5 jours"],
                        ],
                    ],
                    [
                        'fr' => 'Glacière 50L', 'en' => '50L cooler', 'price' => 3000, 'deposit' => 6000, 'sale' => 55000, 'stock' => 20, 'rating' => 4.2, 'rating_count' => 11,
                        'description_fr' => "Glacière compacte de 50 litres, facile à transporter pour les événements de taille moyenne.",
                        'description_en' => 'Compact 50-liter cooler, easy to carry for medium-sized events.',
                        'specifications' => [
                            ['label' => 'Capacité', 'value' => '50 L'],
                            ['label' => 'Dimensions', 'value' => '60 x 38 x 38 cm'],
                        ],
                    ],
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
                        'description_fr' => $item['description_fr'] ?? null,
                        'description_en' => $item['description_en'] ?? null,
                        'specifications' => $item['specifications'] ?? null,
                        'unit_label' => 'unité',
                        'rental_price_per_day' => $item['price'],
                        'deposit_amount' => $item['deposit'],
                        'sale_price' => $item['sale'],
                        'rating' => $item['rating'],
                        'rating_count' => $item['rating_count'],
                        'total_stock' => $item['stock'],
                        'min_rental_quantity' => 1,
                        'is_active' => true,
                    ],
                );
            }
        }
    }
}
