<?php

namespace Tests\Feature;

use App\Models\Item;
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

    public function test_a_guest_can_request_a_quote_and_receives_a_pdf_and_whatsapp_message(): void
    {
        $item = Item::factory()->create(['total_stock' => 10, 'rental_price_per_day' => 1000]);

        $response = $this->postJson('/api/v1/reservations', [
            'event_start_date' => now()->addDays(3)->toDateString(),
            'event_end_date' => now()->addDays(5)->toDateString(),
            'delivery_method' => 'pickup',
            'customer' => [
                'first_name' => 'Ama',
                'last_name' => 'Kodjo',
                'email' => 'ama@example.com',
                'phone' => '+22890000001',
            ],
            'items' => [
                ['item_id' => $item->id, 'quantity' => 2],
            ],
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.status', 'quote_sent');
        $response->assertJsonPath('data.total', '4000.00');
        $response->assertJsonPath('data.items.0.quantity', 2);
        $this->assertNotNull($response->json('data.quote_pdf_url'));
        $this->assertStringContainsString('RES-', $response->json('data.whatsapp_message'));

        $this->assertDatabaseHas('reservations', ['status' => 'quote_sent']);
        Storage::disk('public')->assertExists('quotes/'.$response->json('data.reference').'.pdf');
    }

    public function test_it_rejects_a_quote_that_exceeds_available_stock(): void
    {
        $item = Item::factory()->create(['total_stock' => 5]);

        $response = $this->postJson('/api/v1/reservations', [
            'event_start_date' => now()->addDay()->toDateString(),
            'event_end_date' => now()->addDays(2)->toDateString(),
            'delivery_method' => 'pickup',
            'customer' => [
                'first_name' => 'Ama',
                'last_name' => 'Kodjo',
                'email' => 'ama@example.com',
                'phone' => '+22890000001',
            ],
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

        $payload = fn (int $quantity, string $email) => [
            'event_start_date' => now()->addDay()->toDateString(),
            'event_end_date' => now()->addDays(2)->toDateString(),
            'delivery_method' => 'pickup',
            'customer' => [
                'first_name' => 'Client', 'last_name' => $email, 'email' => $email, 'phone' => '+22890000000',
            ],
            'items' => [['item_id' => $item->id, 'quantity' => $quantity]],
        ];

        $this->postJson('/api/v1/reservations', $payload(7, 'first@example.com'))->assertCreated();

        // Only 3 units are left for the same period; asking for 4 must fail.
        $this->postJson('/api/v1/reservations', $payload(4, 'second@example.com'))->assertStatus(422);

        // But 3 units still fit.
        $this->postJson('/api/v1/reservations', $payload(3, 'third@example.com'))->assertCreated();
    }
}
