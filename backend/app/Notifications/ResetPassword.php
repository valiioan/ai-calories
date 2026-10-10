<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPassword extends BaseResetPassword
{
    public static ?string $latestRelativeUrl = null;

    public function toMail($notifiable): MailMessage
    {
        $relative = '/reset-password?'.http_build_query([
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        if (config('app.expose_password_reset_url')) {
            self::$latestRelativeUrl = $relative;
        }

        $frontend = strtok((string) config('app.frontend_url'), ',') ?: (string) config('app.url');
        $url = rtrim((string) $frontend, '/').$relative;
        $english = $notifiable->settings?->locale === 'en';

        if ($english) {
            return (new MailMessage)
                ->subject('Reset your '.config('app.name').' password')
                ->line('You are receiving this message because a password reset was requested for your account.')
                ->action('Reset password', $url)
                ->line('If you did not request a reset, you can ignore this message.');
        }

        return (new MailMessage)
            ->subject('Resetare parolă '.config('app.name'))
            ->line('Primești acest mesaj deoarece a fost cerută resetarea parolei pentru contul tău.')
            ->action('Resetează parola', $url)
            ->line('Dacă nu ai cerut resetarea, poți ignora mesajul.');
    }
}
