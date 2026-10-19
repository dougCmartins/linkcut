<?php

declare(strict_types=1);

namespace App\Notifications\Channels;

use App\Sms\SmsSender;
use Illuminate\Notifications\Notification;

/** Notification channel: $notification->toSms() → SmsSender. */
final class SmsChannel
{
    public function __construct(private readonly SmsSender $sender) {}

    public function send(object $notifiable, Notification $notification): void
    {
        $phone = $notifiable->routeNotificationFor('sms', $notification);
        if (! $phone || ! method_exists($notification, 'toSms')) {
            return;
        }

        $this->sender->send((string) $phone, $notification->toSms($notifiable));
    }
}
