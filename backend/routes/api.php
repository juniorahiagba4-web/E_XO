<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ContactMessageController;
use App\Http\Controllers\Api\DeliverySlotController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\ItemController;
use App\Http\Controllers\Api\PromotionController;
use App\Http\Controllers\Api\ReservationController;
use App\Http\Controllers\Api\SiteSettingController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('categories', [CategoryController::class, 'index']);
    Route::get('site-settings', [SiteSettingController::class, 'index']);

    Route::get('items', [ItemController::class, 'index']);
    Route::get('items/{item:id}/availability', [ItemController::class, 'availability']);
    Route::get('items/{slug}', [ItemController::class, 'show']);

    Route::get('promotions', [PromotionController::class, 'index']);
    Route::post('promotions/validate-code', [PromotionController::class, 'validateCode'])
        ->middleware('throttle:20,1');

    Route::get('delivery-slots', [DeliverySlotController::class, 'index']);

    Route::post('reservations', [ReservationController::class, 'store'])
        ->middleware('throttle:10,1');
    Route::get('reservations/{reference}', [ReservationController::class, 'show'])
        ->middleware('throttle:30,1');
    Route::get('me/reservations', [ReservationController::class, 'mine'])
        ->middleware('auth:sanctum');
    Route::get('me/favorites', [FavoriteController::class, 'index'])
        ->middleware('auth:sanctum');
    Route::post('items/{item:id}/favorite', [FavoriteController::class, 'toggle'])
        ->middleware('auth:sanctum');

    Route::post('contact-messages', [ContactMessageController::class, 'store'])
        ->middleware('throttle:10,1');

    Route::post('auth/register', [AuthController::class, 'register'])
        ->middleware('throttle:10,1');
    Route::post('auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1');
    Route::post('auth/logout', [AuthController::class, 'logout'])
        ->middleware('auth:sanctum');
    Route::get('auth/me', [AuthController::class, 'me'])
        ->middleware('auth:sanctum');
});
