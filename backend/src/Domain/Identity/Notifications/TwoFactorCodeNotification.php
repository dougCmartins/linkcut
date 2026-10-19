<?php

declare(strict_types=1);

namespace Domain\Identity\Notifications;

use App\Notifications\Channels\SmsChannel;
use Domain\Identity\Enums\TwoFactorChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sends the 2FA code. Queued (failures show up in Pulse) and encrypted, so the
 * code is never stored in plain text in the jobs table.
 */
final class TwoFactorCodeNotification extends Notification implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $code,
        private readonly TwoFactorChannel $channel,
        public readonly int $tenantId,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return [$this->channel === TwoFactorChannel::Sms ? SmsChannel::class : 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $minutes = (int) config('identity.two_factor.code_ttl_minutes');

        return (new MailMessage)
            ->subject(__('identity.code_mail.subject'))
            ->greeting(__('identity.code_mail.greeting'))
            ->line(__('identity.code_mail.intro'))
            ->line('**'.$this->code.'**')
            ->line(__('identity.code_mail.expires', ['minutes' => $minutes]))
            ->line(__('identity.code_mail.ignore'));
    }

    public function toSms(object $notifiable): string
    {
        return __('identity.code_sms', [
            'code' => $this->code,
            'minutes' => (int) config('identity.two_factor.code_ttl_minutes'),
        ]);
    }
}
