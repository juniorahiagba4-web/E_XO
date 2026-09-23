<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\ItemReview;
use Illuminate\Database\Seeder;

class ItemReviewSeeder extends Seeder
{
    public function run(): void
    {
        $reviews = [
            'chaise-chiavari-doree' => [
                ['author_name' => 'Akossiwa D.', 'rating' => 5, 'comment' => "Magnifiques chaises, exactement ce qu'il fallait pour notre mariage. Livraison à l'heure."],
                ['author_name' => 'Koffi M.', 'rating' => 4.5, 'comment' => 'Très bon rapport qualité/prix, quelques chaises un peu usées mais rien de grave.'],
                ['author_name' => 'Sena A.', 'rating' => 5, 'comment' => "L'équipe a été très professionnelle, je recommande vivement."],
            ],
            'glaciere-100l' => [
                ['author_name' => 'Yawa K.', 'rating' => 5, 'comment' => "Tenue au frais impeccable pendant toute la réception, très pratique."],
                ['author_name' => 'Edem T.', 'rating' => 4.7, 'comment' => "Grande capacité, parfaite pour un événement de 150 personnes."],
            ],
        ];

        foreach ($reviews as $slug => $entries) {
            $item = Item::query()->where('slug', $slug)->first();

            if (! $item) {
                continue;
            }

            foreach ($entries as $entry) {
                ItemReview::query()->firstOrCreate(
                    ['item_id' => $item->id, 'author_name' => $entry['author_name']],
                    ['rating' => $entry['rating'], 'comment' => $entry['comment']],
                );
            }
        }
    }
}
