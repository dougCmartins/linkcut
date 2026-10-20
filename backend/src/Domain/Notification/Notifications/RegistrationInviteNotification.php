<?php

declare(strict_types=1);

namespace Domain\Notification\Notifications;

use Domain\Notification\Data\RegistrationInviteData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Sent once to a client who booked and didn't complete the sign-up. */
final class RegistrationInviteNotification extends Notification implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    public function __construct(public readonly RegistrationInviteData $data)
    {
        $this->afterCommit();
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('client_mail.invite.subject'))
            ->greeting(__('client_mail.greeting', ['name' => $this->data->client_name]))
            ->line(__('client_mail.invite.intro'))
            ->line(__('client_mail.invite.benefits'))
            ->action(__('client_mail.register'), $this->data->registration_url)
            ->line(__('client_mail.opt_out', ['url' => $this->data->opt_out_url]));
    }
}
