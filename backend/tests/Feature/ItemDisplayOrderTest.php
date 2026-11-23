<?php

namespace Tests\Feature;

use App\Filament\Resources\Items\Pages\ListItems;
use App\Models\Item;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ItemDisplayOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_new_item_goes_to_the_end_of_the_catalogue(): void
    {
        $first = Item::factory()->create();
        $second = Item::factory()->create();

        $this->assertGreaterThan($first->sort_order, $second->sort_order);
    }

    public function test_the_website_lists_the_items_in_the_admin_order(): void
    {
        Item::factory()->create(['name_fr' => 'Chaise A', 'name_en' => 'Chaise A', 'sort_order' => 3]);
        Item::factory()->create(['name_fr' => 'Table B', 'name_en' => 'Table B', 'sort_order' => 1]);
        Item::factory()->create(['name_fr' => 'Glacière C', 'name_en' => 'Glacière C', 'sort_order' => 2]);

        $names = collect($this->getJson('/api/v1/items')->assertOk()->json('data'))->pluck('name');

        $this->assertSame(['Table B', 'Glacière C', 'Chaise A'], $names->all());
    }

    public function test_the_price_sort_chosen_by_the_visitor_still_wins(): void
    {
        Item::factory()->create(['name_fr' => 'Cher', 'name_en' => 'Cher', 'sort_order' => 1, 'rental_price_per_day' => 5000]);
        Item::factory()->create(['name_fr' => 'Bon marché', 'name_en' => 'Bon marché', 'sort_order' => 2, 'rental_price_per_day' => 500]);

        $names = collect($this->getJson('/api/v1/items?sort=price_asc')->json('data'))->pluck('name');

        $this->assertSame(['Bon marché', 'Cher'], $names->all());
    }

    public function test_the_admin_reorders_the_items_by_drag_and_drop(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs(User::factory()->create());

        $a = Item::factory()->create(['sort_order' => 1]);
        $b = Item::factory()->create(['sort_order' => 2]);
        $c = Item::factory()->create(['sort_order' => 3]);

        Livewire::test(ListItems::class)
            ->call('reorderTable', [(string) $c->id, (string) $a->id, (string) $b->id]);

        $this->assertSame(1, $c->fresh()->sort_order);
        $this->assertSame(2, $a->fresh()->sort_order);
        $this->assertSame(3, $b->fresh()->sort_order);
    }
}
