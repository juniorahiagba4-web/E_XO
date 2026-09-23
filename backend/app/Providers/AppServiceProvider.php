<?php

namespace App\Providers;

use App\Models\ContactMessage;
use App\Models\Reservation;
use App\Observers\ContactMessageObserver;
use App\Observers\ReservationObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Reservation::observe(ReservationObserver::class);
        ContactMessage::observe(ContactMessageObserver::class);
    }
}
