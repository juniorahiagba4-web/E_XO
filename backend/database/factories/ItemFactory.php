<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Item>
 */
class ItemFactory extends Factory
{
    protected $model = Item::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->words(3, true);

        return [
            'category_id' => Category::factory(),
            'name_fr' => ucfirst($name),
            'name_en' => ucfirst($name),
            'slug' => Str::slug($name),
            'description_fr' => $this->faker->sentence(),
            'description_en' => $this->faker->sentence(),
            'sku' => strtoupper($this->faker->unique()->bothify('SKU-####')),
            'unit_label' => 'unité',
            'rental_price_per_day' => $this->faker->numberBetween(500, 15000),
            'deposit_amount' => $this->faker->numberBetween(1000, 20000),
            'total_stock' => $this->faker->numberBetween(10, 200),
            'min_rental_quantity' => 1,
            'is_active' => true,
        ];
    }
}
