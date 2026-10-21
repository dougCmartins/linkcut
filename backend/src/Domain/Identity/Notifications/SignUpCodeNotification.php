<?php

declare(strict_types=1);

namespace Domain\Identity\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Confirms the e-mail of a barber signing up. Queued and encrypted (the code never plain in the jobs table). */
final class SignUpCodeNotification extends Notification implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    public function __construct(public readonly string $code) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('identity.sign_up_mail.subject'))
            ->greeting(__('identity.code_mail.greeting'))
            ->line(__('identity.sign_up_mail.intro'))
            ->line('**'.$this->code.'**')
            ->line(__('identity.code_mail.expires', ['minutes' => (int) config('identity.sign_up.code_ttl_minutes')]))
            ->line(__('identity.sign_up_mail.ignore'));
    }
}
