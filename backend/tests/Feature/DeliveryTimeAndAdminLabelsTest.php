<?php

namespace Tests\Feature;

use App\Filament\Resources\Reservations\Pages\ListReservations;
use App\Models\Customer;
use App\Models\Item;
use App\Models\Reservation;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class DeliveryTimeAndAdminLabelsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function quote(array $overrides): array
    {
        $user = User::factory()->create();
        Customer::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user, 'sanctum');
        $item = Item::factory()->create(['total_stock' => 10, 'rental_price_per_day' => 1000, 'sale_price' => 5000]);

        return array_merge([
            'type' => 'rental',
            'event_start_date' => now()->addDays(3)->toDateString(),
            'event_end_date' => now()->addDays(5)->toDateString(),
            'delivery_method' => 'delivery',
            'delivery_address' => 'Bè Kpota, Lomé',
            'delivery_time' => '14:30',
            'items' => [['item_id' => $item->id, 'quantity' => 2]],
        ], $overrides);
    }

    public function test_the_wished_delivery_time_is_kept_with_the_rental_quote(): void
    {
        $response = $this->postJson('/api/v1/reservations', $this->quote([]))->assertCreated();

        $reservation = Reservation::query()->firstOrFail();
        $this->assertSame('14:30', $reservation->delivery_time);
        $this->assertSame(now()->addDays(3)->toDateString(), $reservation->delivery_date->toDateString());
        $this->assertStringContainsString('Livraison souhaitée : le '.now()->addDays(3)->format('d/m/Y').' à 14h30', $response->json('data.whatsapp_message'));
    }

    public function test_a_purchase_carries_its_own_delivery_day(): void
    {
        $this->postJson('/api/v1/reservations', $this->quote([
            'type' => 'purchase',
            'event_start_date' => null,
            'event_end_date' => null,
            'delivery_date' => now()->addDays(7)->toDateString(),
        ]))->assertCreated();

        $reservation = Reservation::query()->firstOrFail();
        $this->assertSame(now()->addDays(7)->toDateString(), $reservation->delivery_date->toDateString());
        $this->assertSame('14:30', $reservation->delivery_time);
    }

    public function test_a_pickup_ignores_any_delivery_time(): void
    {
        $this->postJson('/api/v1/reservations', $this->quote(['delivery_method' => 'pickup', 'delivery_address' => null]))
            ->assertCreated();

        $this->assertNull(Reservation::query()->firstOrFail()->delivery_time);
    }

    public function test_a_malformed_time_is_rejected(): void
    {
        $this->postJson('/api/v1/reservations', $this->quote(['delivery_time' => '2pm']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('delivery_time');
    }

    public function test_the_admin_list_shows_french_words_and_the_delivery_time(): void
    {
        $this->postJson('/api/v1/reservations', $this->quote([]))->assertCreated();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs(User::factory()->create());

        Livewire::test(ListReservations::class)
            ->assertSee('Envoyé')
            ->assertSee('À domicile')
            ->assertSee('à 14h30')
            ->assertDontSee('>quote_sent<', escape: false);
    }
}
