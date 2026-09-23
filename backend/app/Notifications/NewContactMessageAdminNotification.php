<?php

namespace App\Notifications;

use App\Models\ContactMessage;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewContactMessageAdminNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected ContactMessage $contactMessage) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Nouveau message de contact — {$this->contactMessage->subject}")
            ->line("Un nouveau message a été envoyé par {$this->contactMessage->name} ({$this->contactMessage->email}).")
            ->line($this->contactMessage->message)
            ->action('Voir le message', url('/admin/contact-messages'));
    }

    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title('Nouveau message de contact')
            ->body("{$this->contactMessage->name} — {$this->contactMessage->subject}")
            ->icon('heroicon-o-envelope')
            ->actions([
                Action::make('view')
                    ->label('Voir')
                    ->url('/admin/contact-messages'),
            ])
            ->getDatabaseMessage();
    }
}
