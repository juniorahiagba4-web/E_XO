<?php

namespace Tests\Feature;

use App\Filament\Resources\Reservations\Pages\CreateReservation;
use App\Models\Customer;
use App\Models\Item;
use App\Models\Reservation;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Components\Repeater;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ManualOrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Repeater::fake();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs(User::factory()->create());
    }

    public function test_a_phone_rental_is_recorded_with_its_items_and_typed_prices(): void
    {
        $customer = Customer::factory()->create();
        $chair = Item::factory()->create(['total_stock' => 100, 'rental_price_per_day' => 1500]);
        $table = Item::factory()->create(['total_stock' => 10, 'rental_price_per_day' => 2500]);

        Livewire::test(CreateReservation::class)
            ->fillForm([
                'type' => 'rental',
                'status' => 'confirmed',
                'customer_id' => $customer->id,
                'event_start_date' => now()->addDays(2)->toDateString(),
                'event_end_date' => now()->addDays(4)->toDateString(),
                'delivery_method' => 'delivery',
                'delivery_address' => 'Agoè, Lomé',
                'delivery_time' => '08:00',
                'lines' => [
                    ['item_id' => $chair->id, 'quantity' => 50, 'unit_price' => 1200],
                    ['item_id' => $table->id, 'quantity' => 5, 'unit_price' => 2500],
                ],
                'discount' => 10000,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $reservation = Reservation::query()->with('items')->sole();
        $this->assertSame('confirmed', $reservation->status);
        $this->assertNull($reservation->hold_expires_at);
        $this->assertSame('08:00', $reservation->return_time);
        // (50 × 1 200 + 5 × 2 500) × 2 days = 145 000, minus 10 000.
        $this->assertSame('145000.00', $reservation->subtotal);
        $this->assertSame('135000.00', $reservation->total);
        $this->assertSame('1200.00', $reservation->items->firstWhere('item_id', $chair->id)->unit_price_per_day);
        Storage::disk('public')->assertExists($reservation->quote_pdf_path);
    }

    public function test_a_purchase_line_left_without_price_stays_sur_devis(): void
    {
        $customer = Customer::factory()->create();
        $chair = Item::factory()->create(['total_stock' => 100, 'sale_price' => null, 'sale_price_on_request' => true]);
        $cloth = Item::factory()->create(['total_stock' => 100, 'sale_price' => 8000]);

        Livewire::test(CreateReservation::class)
            ->fillForm([
                'type' => 'purchase',
                'status' => 'quote_sent',
                'customer_id' => $customer->id,
                'purchase_date' => now()->toDateString(),
                'delivery_method' => 'pickup',
                'lines' => [
                    ['item_id' => $chair->id, 'quantity' => 20, 'unit_price' => null],
                    ['item_id' => $cloth->id, 'quantity' => 3, 'unit_price' => 8000],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $reservation = Reservation::query()->with('items')->sole();
        $this->assertNull($reservation->items->firstWhere('item_id', $chair->id)->unit_sale_price);
        $this->assertSame('24000.00', $reservation->total);
        $this->assertSame(now()->toDateString(), $reservation->purchase_date->toDateString());
    }

    public function test_more_than_the_stock_is_refused(): void
    {
        $customer = Customer::factory()->create();
        $item = Item::factory()->create(['total_stock' => 5, 'rental_price_per_day' => 1000]);

        Livewire::test(CreateReservation::class)
            ->fillForm([
                'type' => 'rental',
                'status' => 'confirmed',
                'customer_id' => $customer->id,
                'event_start_date' => now()->addDays(2)->toDateString(),
                'event_end_date' => now()->addDays(3)->toDateString(),
                'delivery_method' => 'pickup',
                'lines' => [['item_id' => $item->id, 'quantity' => 6, 'unit_price' => 1000]],
            ])
            ->call('create')
            ->assertNotified('Stock insuffisant');

        $this->assertSame(0, Reservation::query()->count());
    }

    public function test_a_customer_without_email_can_be_saved(): void
    {
        $customer = Customer::factory()->create(['email' => null]);

        $this->assertNull($customer->fresh()->email);
    }
}
