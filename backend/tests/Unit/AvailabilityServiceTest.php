<?php

namespace Tests\Unit;

use App\Models\Item;
use App\Models\Reservation;
use App\Models\ReservationItem;
use App\Services\AvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AvailabilityServiceTest extends TestCase
{
    use RefreshDatabase;

    private AvailabilityService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AvailabilityService;
    }

    public function test_full_stock_is_available_when_there_are_no_reservations(): void
    {
        $item = Item::factory()->create(['total_stock' => 100]);

        $available = $this->service->availableQuantity($item, Carbon::parse('2026-10-01'), Carbon::parse('2026-10-05'));

        $this->assertSame(100, $available);
    }

    public function test_overlapping_reservation_reduces_availability_for_the_whole_requested_range(): void
    {
        $item = Item::factory()->create(['total_stock' => 100]);

        $this->bookItem($item, quantity: 40, start: '2026-10-02', end: '2026-10-03');

        // The query range only partially overlaps the booking, but the peak
        // day within the overlap (Oct 2-3) still blocks 40 units.
        $available = $this->service->availableQuantity($item, Carbon::parse('2026-10-01'), Carbon::parse('2026-10-05'));

        $this->assertSame(60, $available);
    }

    public function test_non_overlapping_reservation_does_not_affect_availability(): void
    {
        $item = Item::factory()->create(['total_stock' => 100]);

        $this->bookItem($item, quantity: 40, start: '2026-09-01', end: '2026-09-05');

        $available = $this->service->availableQuantity($item, Carbon::parse('2026-10-01'), Carbon::parse('2026-10-05'));

        $this->assertSame(100, $available);
    }

    public function test_two_back_to_back_bookings_only_peak_on_their_own_days(): void
    {
        $item = Item::factory()->create(['total_stock' => 50]);

        $this->bookItem($item, quantity: 50, start: '2026-10-01', end: '2026-10-02');
        $this->bookItem($item, quantity: 50, start: '2026-10-03', end: '2026-10-04');

        // A range spanning both bookings sees the peak (50) on either side,
        // so nothing is left...
        $this->assertSame(0, $this->service->availableQuantity(
            $item, Carbon::parse('2026-10-01'), Carbon::parse('2026-10-04'),
        ));

        // ...but a fresh day right after both bookings end is fully free.
        $this->assertSame(50, $this->service->availableQuantity(
            $item, Carbon::parse('2026-10-05'), Carbon::parse('2026-10-06'),
        ));
    }

    public function test_cancelled_reservations_do_not_block_stock(): void
    {
        $item = Item::factory()->create(['total_stock' => 100]);

        $this->bookItem($item, quantity: 40, start: '2026-10-01', end: '2026-10-05', status: 'cancelled');

        $available = $this->service->availableQuantity($item, Carbon::parse('2026-10-01'), Carbon::parse('2026-10-05'));

        $this->assertSame(100, $available);
    }

    public function test_expired_quote_hold_does_not_block_stock(): void
    {
        $item = Item::factory()->create(['total_stock' => 100]);

        $this->bookItem(
            $item, quantity: 40, start: '2026-10-01', end: '2026-10-05',
            status: 'quote_sent', holdExpiresAt: Carbon::now()->subDay(),
        );

        $available = $this->service->availableQuantity($item, Carbon::parse('2026-10-01'), Carbon::parse('2026-10-05'));

        $this->assertSame(100, $available);
    }

    private function bookItem(
        Item $item,
        int $quantity,
        string $start,
        string $end,
        string $status = 'confirmed',
        ?Carbon $holdExpiresAt = null,
    ): Reservation {
        $reservation = Reservation::factory()->create([
            'status' => $status,
            'event_start_date' => $start,
            'event_end_date' => $end,
            'hold_expires_at' => $holdExpiresAt,
        ]);

        ReservationItem::factory()->create([
            'reservation_id' => $reservation->id,
            'item_id' => $item->id,
            'quantity' => $quantity,
        ]);

        return $reservation;
    }
}
