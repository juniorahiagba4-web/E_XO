<?php

namespace App\Services;

use App\Models\Item;
use App\Models\Reservation;
use App\Models\ReservationItem;
use Illuminate\Support\Carbon;

/**
 * Computes real-time rental availability for an item over a date range.
 *
 * Stock is not a simple counter: an item is "reserved" for the whole span of
 * every overlapping booking, so the same unit can be booked again once a
 * previous reservation's period has passed. We use a sweep-line (difference
 * array) over the requested range to find the single worst-case day, and
 * available stock is the item's total minus that peak.
 */
class AvailabilityService
{
    public function availableQuantity(Item $item, Carbon $start, Carbon $end, ?int $excludeReservationId = null): int
    {
        $peak = $this->peakReservedQuantity($item->id, $start, $end, $excludeReservationId);

        return max(0, $item->total_stock - $peak);
    }

    /**
     * Purchases have no time window: once a unit is sold it never comes back,
     * so availability is simply the total minus everything already sold
     * (any non-draft, non-cancelled purchase order).
     */
    public function purchasableQuantity(Item $item, ?int $excludeReservationId = null): int
    {
        $sold = ReservationItem::query()
            ->where('item_id', $item->id)
            ->whereHas('reservation', function ($query) use ($excludeReservationId) {
                $query->where('type', 'purchase')
                    ->whereNotIn('status', ['draft', 'cancelled'])
                    ->when($excludeReservationId, fn ($q) => $q->whereKeyNot($excludeReservationId));
            })
            ->sum('quantity');

        return max(0, $item->total_stock - (int) $sold);
    }

    public function peakReservedQuantity(int $itemId, Carbon $start, Carbon $end, ?int $excludeReservationId = null): int
    {
        $start = $start->copy()->startOfDay();
        $end = $end->copy()->startOfDay();

        if ($end->lt($start)) {
            return 0;
        }

        $reservations = Reservation::query()
            ->blockingStock()
            ->when($excludeReservationId, fn ($q) => $q->whereKeyNot($excludeReservationId))
            ->whereDate('event_start_date', '<=', $end)
            ->whereDate('event_end_date', '>=', $start)
            ->with(['items' => fn ($q) => $q->where('item_id', $itemId)])
            ->get();

        $totalDays = $start->diffInDays($end) + 1;
        // One extra slot at the end to receive the "decrement the day after it ends" marker.
        $diff = array_fill(0, $totalDays + 1, 0);

        foreach ($reservations as $reservation) {
            $quantity = (int) $reservation->items->sum('quantity');

            if ($quantity === 0) {
                continue;
            }

            $overlapStart = $reservation->event_start_date->greaterThan($start) ? $reservation->event_start_date : $start;
            $overlapEnd = $reservation->event_end_date->lessThan($end) ? $reservation->event_end_date : $end;

            $startOffset = $start->diffInDays($overlapStart);
            $endOffset = $start->diffInDays($overlapEnd);

            $diff[$startOffset] += $quantity;
            $diff[$endOffset + 1] -= $quantity;
        }

        $peak = 0;
        $running = 0;

        foreach ($diff as $delta) {
            $running += $delta;
            $peak = max($peak, $running);
        }

        return $peak;
    }
}
