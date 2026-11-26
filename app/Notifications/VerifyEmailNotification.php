<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class VerifyEmailNotification extends Notification
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes((int) config('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );
        $data = [
            'userName' => $notifiable->name,
            'verificationUrl' => $verificationUrl,
            'expireMinutes' => (int) config('auth.verification.expire', 60),
        ];

        return (new MailMessage)
            ->subject('Verifikasi Email Akun SIPACAK')
            ->view('emails.verify-email', $data)
            ->text('emails.verify-email-text', $data);
    }
}
