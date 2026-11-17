<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class AccountLocked extends Notification
{
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = URL::temporarySignedRoute(
            'account.unlock',
            now()->addMinutes(60),
            ['user' => $notifiable->id]
        );

        return (new MailMessage)
            ->subject('Your Calendar App account has been locked')
            ->line('Your account was locked after 5 failed login attempts.')
            ->action('Unlock my account', $url)
            ->line('This link expires in 60 minutes.')
            ->line("If these attempts weren't you, change your password after unlocking.");
    }
}