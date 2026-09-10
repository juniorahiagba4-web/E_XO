<?php

namespace Tests\Feature;

use App\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ExpireStaleQuotesTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_cancels_quotes_past_their_hold_expiry(): void
    {
        $expired = Reservation::factory()->create([
            'status' => 'quote_sent',
            'hold_expires_at' => Carbon::now()->subHour(),
        ]);

        $stillHeld = Reservation::factory()->create([
            'status' => 'quote_sent',
            'hold_expires_at' => Carbon::now()->addHour(),
        ]);

        $confirmed = Reservation::factory()->confirmed()->create([
            'hold_expires_at' => Carbon::now()->subHour(),
        ]);

        $this->artisan('reservations:expire-stale-quotes')->assertExitCode(0);

        $this->assertSame('cancelled', $expired->fresh()->status);
        $this->assertSame('quote_sent', $stillHeld->fresh()->status);
        $this->assertSame('confirmed', $confirmed->fresh()->status);
    }
}
