<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReservationCreationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function actingAsCustomer(): User
    {
        $user = User::factory()->create();
        Customer::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user, 'sanctum');

        return $user;
    }

    public function test_a_customer_can_request_a_rental_quote_and_receives_a_pdf_and_whatsapp_message(): void
    {
        $this->actingAsCustomer();
        $item = Item::factory()->create(['total_stock' => 10, 'rental_price_per_day' => 1000]);

        $response = $this->postJson('/api/v1/reservations', [
            'type' => 'rental',
            'event_start_date' => now()->addDays(3)->toDateString(),
            'event_end_date' => now()->addDays(5)->toDateString(),
            'delivery_method' => 'pickup',
            'items' => [
                ['item_id' => $item->id, 'quantity' => 2],
            ],
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.status', 'quote_sent');
        $response->assertJsonPath('data.type', 'rental');
        $response->assertJsonPath('data.total', '4000.00');
        $response->assertJsonPath('data.items.0.quantity', 2);
        $this->assertNotNull($response->json('data.quote_pdf_url'));
        $this->assertStringContainsString('RES-', $response->json('data.whatsapp_message'));

        $this->assertDatabaseHas('reservations', ['status' => 'quote_sent']);
        Storage::disk('public')->assertExists('quotes/'.$response->json('data.reference').'.pdf');
    }

    public function test_it_rejects_a_quote_that_exceeds_available_stock(): void
    {
        $this->actingAsCustomer();
        $item = Item::factory()->create(['total_stock' => 5]);

        $response = $this->postJson('/api/v1/reservations', [
            'type' => 'rental',
            'event_start_date' => now()->addDay()->toDateString(),
            'event_end_date' => now()->addDays(2)->toDateString(),
            'delivery_method' => 'pickup',
            'items' => [
                ['item_id' => $item->id, 'quantity' => 6],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('available_quantity', 5);
        $this->assertDatabaseCount('reservations', 0);
    }

    public function test_a_second_overlapping_request_only_sees_the_remaining_stock(): void
    {
        $item = Item::factory()->create(['total_stock' => 10]);

        $payload = fn (int $quantity) => [
            'type' => 'rental',
            'event_start_date' => now()->addDay()->toDateString(),
            'event_end_date' => now()->addDays(2)->toDateString(),
            'delivery_method' => 'pickup',
            'items' => [['item_id' => $item->id, 'quantity' => $quantity]],
        ];

        $this->actingAsCustomer();
        $this->postJson('/api/v1/reservations', $payload(7))->assertCreated();

        // Only 3 units are left for the same period; asking for 4 must fail.
        $this->actingAsCustomer();
        $this->postJson('/api/v1/reservations', $payload(4))->assertStatus(422);

        // But 3 units still fit.
        $this->actingAsCustomer();
        $this->postJson('/api/v1/reservations', $payload(3))->assertCreated();
    }

    public function test_a_customer_can_buy_an_item_outright(): void
    {
        $this->actingAsCustomer();
        $item = Item::factory()->create(['total_stock' => 10, 'sale_price' => 15000]);

        $response = $this->postJson('/api/v1/reservations', [
            'type' => 'purchase',
            'delivery_method' => 'pickup',
            'items' => [
                ['item_id' => $item->id, 'quantity' => 2],
            ],
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.type', 'purchase');
        $response->assertJsonPath('data.total', '30000.00');
        $response->assertJsonPath('data.items.0.quantity', 2);
    }

    public function test_a_guest_can_request_a_quote_by_supplying_their_own_contact_details(): void
    {
        $item = Item::factory()->create(['total_stock' => 10, 'rental_price_per_day' => 1000]);

        $response = $this->postJson('/api/v1/reservations', [
            'type' => 'rental',
            'event_start_date' => now()->addDay()->toDateString(),
            'event_end_date' => now()->addDays(2)->toDateString(),
            'delivery_method' => 'pickup',
            'customer' => [
                'first_name' => 'Ama',
                'last_name' => 'Kodjo',
                'email' => 'ama.guest@example.com',
                'phone' => '+22890000001',
            ],
            'items' => [['item_id' => $item->id, 'quantity' => 1]],
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('customers', ['email' => 'ama.guest@example.com']);
    }

    public function test_a_guest_without_contact_details_is_rejected(): void
    {
        $item = Item::factory()->create(['total_stock' => 10]);

        $response = $this->postJson('/api/v1/reservations', [
            'type' => 'rental',
            'event_start_date' => now()->addDay()->toDateString(),
            'event_end_date' => now()->addDays(2)->toDateString(),
            'delivery_method' => 'pickup',
            'items' => [['item_id' => $item->id, 'quantity' => 1]],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['customer.first_name', 'customer.last_name', 'customer.email', 'customer.phone']);
    }
}
