<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly string $token)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $resetUrl = URL::route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);
        $minutes = (int) config('auth.passwords.users.expire', 60);
        $data = [
            'userName' => $notifiable->name,
            'resetUrl' => $resetUrl,
            'expireMinutes' => $minutes,
        ];

        return (new MailMessage)
            ->subject('Atur Ulang Password SIPACAK')
            ->view('emails.password-reset', $data)
            ->text('emails.password-reset-text', $data);
    }
}
