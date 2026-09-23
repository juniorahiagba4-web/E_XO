<?php

namespace App\Observers;

use App\Models\ContactMessage;
use App\Models\User;
use App\Notifications\NewContactMessageAdminNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

class ContactMessageObserver
{
    /**
     * Handle the ContactMessage "created" event.
     */
    public function created(ContactMessage $contactMessage): void
    {
        // Same as ReservationObserver: never let a notification failure
        // block the contact message from being saved.
        try {
            $recipients = User::whereHas('roles', fn ($query) => $query->whereIn('name', ['admin', 'manager']))
                ->where('is_active', true)
                ->get();

            Notification::send($recipients, new NewContactMessageAdminNotification($contactMessage));
        } catch (Throwable $e) {
            Log::warning('Failed to notify admins of new contact message', [
                'contact_message_id' => $contactMessage->id,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
