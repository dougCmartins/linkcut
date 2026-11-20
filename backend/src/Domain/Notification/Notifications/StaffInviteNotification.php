<?php

declare(strict_types=1);

namespace Domain\Notification\Notifications;

use Domain\Notification\Data\StaffInviteMailData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class StaffInviteNotification extends Notification implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    public function __construct(public readonly StaffInviteMailData $data)
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
            ->subject(__('team_mail.invite.subject', ['unit' => $this->data->unit_name]))
            ->greeting(__('team_mail.greeting', ['name' => $this->data->barber_name]))
            ->line(__('team_mail.invite.intro', ['unit' => $this->data->unit_name]))
            ->action(__('team_mail.invite.action'), $this->data->invite_url)
            ->line(__('team_mail.invite.expiry'));
    }
}
