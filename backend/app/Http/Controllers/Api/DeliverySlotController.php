<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DeliverySlotResource;
use App\Models\DeliverySlotTemplate;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;

class DeliverySlotController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'type' => ['required', 'in:delivery,pickup'],
        ]);

        $date = Carbon::parse($validated['date']);
        $type = $validated['type'];

        $templates = DeliverySlotTemplate::query()
            ->where('is_active', true)
            ->get()
            ->filter(fn (DeliverySlotTemplate $t) => $t->appliesToWeekday($date->dayOfWeek) && $t->matchesType($type));

        $bookedCounts = Reservation::query()
            ->where('status', '!=', 'cancelled')
            ->where(function ($query) use ($date) {
                $query->where(function ($q) use ($date) {
                    $q->whereNotNull('delivery_slot_template_id')->whereDate('delivery_date', $date);
                })->orWhere(function ($q) use ($date) {
                    $q->whereNotNull('return_slot_template_id')->whereDate('return_date', $date);
                });
            })
            ->get(['delivery_slot_template_id', 'delivery_date', 'return_slot_template_id', 'return_date']);

        $slots = $templates->map(function (DeliverySlotTemplate $template) use ($date, $bookedCounts) {
            $booked = $bookedCounts->filter(function ($reservation) use ($template, $date) {
                $matchesDelivery = $reservation->delivery_slot_template_id === $template->id
                    && $reservation->delivery_date?->isSameDay($date);
                $matchesReturn = $reservation->return_slot_template_id === $template->id
                    && $reservation->return_date?->isSameDay($date);

                return $matchesDelivery || $matchesReturn;
            })->count();

            return [
                'template' => $template,
                'date' => $date->toDateString(),
                'remaining_capacity' => max(0, $template->capacity - $booked),
            ];
        });

        return DeliverySlotResource::collection($slots);
    }
}
