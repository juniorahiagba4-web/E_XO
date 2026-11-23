<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Item;
use App\Models\ItemReview;
use App\Models\Reservation;
use App\Models\ReservationItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PurgeTestDataTest extends TestCase
{
    use RefreshDatabase;

    private function seedTestData(): array
    {
        Storage::fake('public');
        Storage::disk('public')->put('quotes/RES-1.pdf', 'pdf');
        Storage::disk('public')->put('items/chaise.jpg', 'photo');

        $admin = User::factory()->create(['email' => 'contact@laperledor-togo.com']);
        $admin->assignRole(Role::findOrCreate('admin'));
        $manager = User::factory()->create();
        $manager->assignRole(Role::findOrCreate('manager'));
        $shopper = User::factory()->create(['email' => 'client@test.tg']);

        $item = Item::factory()->create(['rating' => 4.5, 'rating_count' => 142]);
        $customer = Customer::factory()->create(['user_id' => $shopper->id]);
        $reservation = Reservation::factory()->create(['customer_id' => $customer->id]);
        ReservationItem::factory()->create(['reservation_id' => $reservation->id, 'item_id' => $item->id]);
        ItemReview::create(['author_name' => 'Test', 'rating' => 4, 'status' => 'approved', 'item_id' => $item->id, 'reservation_id' => $reservation->id, 'customer_id' => $customer->id]);
        $catalogueReview = ItemReview::create(['author_name' => 'Test', 'rating' => 4, 'status' => 'approved', 'item_id' => $item->id, 'reservation_id' => null, 'customer_id' => null]);

        return compact('admin', 'manager', 'shopper', 'item', 'catalogueReview');
    }

    public function test_without_force_nothing_is_deleted(): void
    {
        $this->seedTestData();

        $this->artisan('app:purge-test-data')->assertSuccessful();

        $this->assertSame(1, Reservation::count());
        $this->assertSame(1, Customer::count());
        Storage::disk('public')->assertExists('quotes/RES-1.pdf');
    }

    public function test_force_empties_the_orders_but_keeps_the_catalogue_and_the_staff(): void
    {
        ['admin' => $admin, 'manager' => $manager, 'shopper' => $shopper, 'item' => $item, 'catalogueReview' => $review] = $this->seedTestData();
        $categories = Category::count();

        $this->artisan('app:purge-test-data', ['--force' => true])->assertSuccessful();

        $this->assertSame(0, Reservation::count());
        $this->assertSame(0, ReservationItem::count());
        $this->assertSame(0, Customer::count());
        $this->assertNull(User::find($shopper->id));
        $this->assertNotNull(User::find($admin->id));
        $this->assertNotNull(User::find($manager->id));

        $this->assertNotNull(Item::find($item->id));
        $this->assertSame($categories, Category::count());
        $this->assertNull($item->fresh()->rating);
        $this->assertNotNull(ItemReview::find($review->id), 'catalogue reviews not tied to a customer stay');
        $this->assertSame(1, ItemReview::count());

        Storage::disk('public')->assertMissing('quotes/RES-1.pdf');
        Storage::disk('public')->assertExists('items/chaise.jpg');

        // The next order starts the numbering again.
        $this->assertStringStartsWith('RES-'.now()->year.'-00001-', Reservation::generateReference());
    }

    public function test_a_customer_account_can_be_kept(): void
    {
        ['shopper' => $shopper] = $this->seedTestData();

        $this->artisan('app:purge-test-data', ['--force' => true, '--keep-user' => ['client@test.tg']])->assertSuccessful();

        $this->assertNotNull(User::find($shopper->id));
    }
}
