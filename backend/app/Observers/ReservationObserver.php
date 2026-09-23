<?php

namespace App\Observers;

use App\Models\Reservation;
use App\Models\User;
use App\Notifications\NewReservationAdminNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

class ReservationObserver
{
    /**
     * Handle the Reservation "created" event.
     */
    public function created(Reservation $reservation): void
    {
        // A notification failure (bad mail config, no admin/manager role
        // seeded yet, etc.) must never block the reservation itself from
        // being created — this is best-effort, not part of the transaction.
        try {
            $recipients = User::whereHas('roles', fn ($query) => $query->whereIn('name', ['admin', 'manager']))
                ->where('is_active', true)
                ->get();

            Notification::send($recipients, new NewReservationAdminNotification($reservation));
        } catch (Throwable $e) {
            Log::warning('Failed to notify admins of new reservation', [
                'reservation_id' => $reservation->id,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
