<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly string $token) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        return (new MailMessage)
            ->subject('Renouvelez votre sceau secret · Le Fil d’Ambre')
            ->markdown('mail.auth.reset-password', [
                'characterName' => $notifiable->name,
                'expiresIn' => (int) config('auth.passwords.users.expire'),
                'url' => $url,
            ]);
    }
}
