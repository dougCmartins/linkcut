<?php

declare(strict_types=1);

namespace Domain\Client\Notifications;

use Domain\Client\Enums\CodePurpose;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** The code to complete the sign-up or set a new password. Queued and encrypted (never plain in the jobs table). */
final class ClientCodeNotification extends Notification implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    public function __construct(private readonly string $code, private readonly CodePurpose $purpose) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $key = "client.code_mail.{$this->purpose->value}";

        return (new MailMessage)
            ->subject(__("{$key}.subject"))
            ->greeting(__('client.code_mail.greeting'))
            ->line(__("{$key}.intro"))
            ->line('**'.$this->code.'**')
            ->line(__('client.code_mail.expires', ['minutes' => (int) config('client.code.ttl_minutes')]))
            ->line(__('client.code_mail.ignore'));
    }
}
