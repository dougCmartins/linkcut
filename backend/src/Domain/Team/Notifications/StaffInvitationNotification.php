<?php

declare(strict_types=1);

namespace Domain\Team\Notifications;

use Domain\Team\Models\StaffInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** The invitation to join a unit's team, with the single-use link (the token travels only in this e-mail). */
final class StaffInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $barbershopName,
        public readonly string $staffName,
        public readonly string $url,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('team.invitation_mail.subject', ['barbershop' => $this->barbershopName]))
            ->greeting(__('team.invitation_mail.greeting', ['name' => $this->staffName]))
            ->line(__('team.invitation_mail.intro', ['barbershop' => $this->barbershopName]))
            ->action(__('team.invitation_mail.action'), $this->url)
            ->line(__('team.invitation_mail.expiry', ['days' => StaffInvitation::TTL_DAYS]))
            ->line(__('team.invitation_mail.ignore'));
    }
}
