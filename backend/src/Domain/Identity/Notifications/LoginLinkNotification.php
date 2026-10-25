<?php

declare(strict_types=1);

namespace Domain\Identity\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** "Encontrar minha página": the sign-in link of the barber's own page (linkcut.app/<slug>/entrar). */
final class LoginLinkNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly string $barbershopName, private readonly string $url) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('identity.login_link_mail.subject'))
            ->greeting(__('identity.code_mail.greeting'))
            ->line(__('identity.login_link_mail.intro', ['barbershop' => $this->barbershopName]))
            ->action(__('identity.login_link_mail.action'), $this->url)
            ->line(__('identity.login_link_mail.tip'))
            ->line(__('identity.login_link_mail.ignore'));
    }
}
