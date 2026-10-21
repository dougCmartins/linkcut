<?php

declare(strict_types=1);

namespace Domain\Identity\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Someone tried to sign up with an e-mail that already has an account: the owner is told to sign in instead. */
final class AccountExistsNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('identity.account_exists_mail.subject'))
            ->greeting(__('identity.code_mail.greeting'))
            ->line(__('identity.account_exists_mail.intro'))
            ->action(__('identity.account_exists_mail.action'), rtrim((string) config('app.frontend_url'), '/').'/entrar')
            ->line(__('identity.account_exists_mail.ignore'));
    }
}
