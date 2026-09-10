<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReservationRequest;
use App\Http\Resources\ReservationResource;
use App\Models\Reservation;
use App\Services\ReservationService;
use Illuminate\Http\JsonResponse;

class ReservationController extends Controller
{
    public function __construct(private readonly ReservationService $reservations) {}

    public function store(StoreReservationRequest $request): JsonResponse
    {
        try {
            $reservation = $this->reservations->createQuote($request->validated());
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

    public function show(string $reference): ReservationResource
    {
        $reservation = Reservation::query()
            ->with(['items.item'])
            ->where('reference', $reference)
            ->firstOrFail();

        return new ReservationResource($reservation);
    }
}
