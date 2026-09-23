<?php

namespace App\Notifications;

use App\Models\Reservation;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewReservationAdminNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected Reservation $reservation)
    {
        $this->reservation->loadMissing('customer');
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $customerName = $this->reservation->customer?->full_name ?? 'Client';
        $label = $this->reservation->type === 'purchase' ? 'commande' : 'devis';

        return (new MailMessage)
            ->subject("Nouveau {$label} — {$this->reservation->reference}")
            ->line("Un nouveau {$label} vient d'être soumis par {$customerName}.")
            ->line("Référence : {$this->reservation->reference}")
            ->line('Total estimé : '.number_format((float) $this->reservation->total, 0, ',', ' ')." {$this->reservation->currency}")
            ->action('Voir la réservation', url("/admin/reservations/{$this->reservation->id}/edit"));
    }

    public function toDatabase(object $notifiable): array
    {
        $customerName = $this->reservation->customer?->full_name ?? 'Client';
        $label = $this->reservation->type === 'purchase' ? 'Nouvelle commande' : 'Nouveau devis';

        return FilamentNotification::make()
            ->title($label)
            ->body("{$customerName} — {$this->reservation->reference}")
            ->icon('heroicon-o-document-text')
            ->actions([
                Action::make('view')
                    ->label('Voir')
                    ->url("/admin/reservations/{$this->reservation->id}/edit"),
            ])
            ->getDatabaseMessage();
    }
}
