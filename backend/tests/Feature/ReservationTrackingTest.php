<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_tracking_a_quote_requires_the_email_on_file(): void
    {
        $customer = Customer::factory()->create(['email' => 'owner@example.com']);
        $reservation = Reservation::factory()->create(['customer_id' => $customer->id]);

        $this->getJson("/api/v1/reservations/{$reservation->reference}?email=owner@example.com")
            ->assertOk()
            ->assertJsonPath('data.reference', $reservation->reference);
    }

    public function test_tracking_a_quote_with_the_wrong_email_is_rejected(): void
    {
        $customer = Customer::factory()->create(['email' => 'owner@example.com']);
        $reservation = Reservation::factory()->create(['customer_id' => $customer->id]);

        $this->getJson("/api/v1/reservations/{$reservation->reference}?email=someone-else@example.com")
            ->assertNotFound();
    }

    public function test_tracking_a_quote_without_an_email_is_rejected(): void
    {
        $customer = Customer::factory()->create();
        $reservation = Reservation::factory()->create(['customer_id' => $customer->id]);

        $this->getJson("/api/v1/reservations/{$reservation->reference}")
            ->assertStatus(422);
    }
}
