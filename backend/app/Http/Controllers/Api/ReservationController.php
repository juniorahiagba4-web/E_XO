<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReservationRequest;
use App\Http\Resources\ReservationResource;
use App\Models\Customer;
use App\Models\Reservation;
use App\Services\ReservationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;

class ReservationController extends Controller
{
    public function __construct(private readonly ReservationService $reservations) {}

    public function store(StoreReservationRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = $request->user('sanctum');

        // Signed-in customers reuse their profile; guests get a customer
        // record matched (or created) by email, same as before accounts existed.
        $customer = $user
            ? $user->customer
            : Customer::query()->updateOrCreate(
                ['email' => $data['customer']['email']],
                Arr::only($data['customer'], ['first_name', 'last_name', 'phone']),
            );

        try {
            $reservation = $this->reservations->createOrder($data, $customer);
        } catch (InsufficientStockException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'item_id' => $exception->itemId,
                'available_quantity' => $exception->availableQuantity,
            ], 422);
        }

        return (new ReservationResource($reservation))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, string $reference): ReservationResource
    {
        // The reference alone (year + sequence + 3 random chars) is guessable
        // enough that it shouldn't be sufficient on its own to view someone
        // else's order — the requester must also know the email on file.
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $reservation = Reservation::query()
            ->with(['items.item'])
            ->where('reference', $reference)
            ->whereHas('customer', fn ($query) => $query->whereRaw('LOWER(email) = ?', [strtolower($validated['email'])]))
            ->firstOrFail();

        return new ReservationResource($reservation);
    }

    public function mine(Request $request): AnonymousResourceCollection
    {
        $reservations = Reservation::query()
            ->with(['items.item'])
            ->where('customer_id', $request->user()->customer?->id)
            ->latest()
            ->paginate(20);

        return ReservationResource::collection($reservations);
    }
}
