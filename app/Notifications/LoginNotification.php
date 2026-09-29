<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\Middleware\RateLimited;

class LoginNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 10;

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * @return array<int, object>
     */
    public function middleware(object $notifiable, string $channel): array
    {
        return $channel === 'mail' ? [new RateLimited('outbound-mail')] : [];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New sign-in to your account')
            ->greeting('Hello '.$notifiable->name)
            ->line('A sign-in from a new device or network was detected on your management account.')
            ->line('If this was not you, please secure your account immediately.');
    }
}
