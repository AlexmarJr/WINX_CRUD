<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class PasswordResetNotification extends ResetPassword implements ShouldQueue
{
    use Queueable;

    public function __construct(string $token)
    {
        parent::__construct($token);

        $this->onQueue('emails');
    }

    protected function resetUrl(mixed $notifiable): string
    {
        return rtrim(config('app.frontend_url'), '/')
            .'/reset-password/'.$this->token.'?email='.rawurlencode($notifiable->getEmailForPasswordReset());
    }

    protected function buildMailMessage($url): MailMessage
    {
        return (new MailMessage)
            ->subject('Redefina sua senha · Winx')
            ->greeting('Olá!')
            ->line('Recebemos um pedido para redefinir a senha da sua conta.')
            ->action('Redefinir senha', $url)
            ->line('Este link expira em '.config('auth.passwords.users.expire').' minutos.')
            ->line('Se você não pediu a redefinição, pode ignorar este e-mail.');
    }
}
