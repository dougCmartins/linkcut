<?php

declare(strict_types=1);

namespace Domain\Orchestrator\StaffMove\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** "A partir de {data} você atende em {filial}": the barber learns where they work now. */
final class StaffMovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $unitName,
        public readonly string $startsOn,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('team_mail.move.subject', ['unit' => $this->unitName]))
            ->line(__('team_mail.move.intro', ['date' => $this->startsOn, 'unit' => $this->unitName]))
            ->line(__('team_mail.move.next'));
    }
}
