<?php

use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\DeliverySlotController;
use App\Http\Controllers\Api\ItemController;
use App\Http\Controllers\Api\ReservationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')->group(function () {
    Route::get('categories', [CategoryController::class, 'index']);

    Route::get('items', [ItemController::class, 'index']);
    Route::get('items/{item:id}/availability', [ItemController::class, 'availability']);
    Route::get('items/{slug}', [ItemController::class, 'show']);

    Route::get('delivery-slots', [DeliverySlotController::class, 'index']);

    Route::post('reservations', [ReservationController::class, 'store'])
        ->middleware('throttle:10,1');
    Route::get('reservations/{reference}', [ReservationController::class, 'show'])
        ->middleware('throttle:30,1');
});
