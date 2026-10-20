<?php

declare(strict_types=1);

namespace Domain\Notification\Support;

use Domain\Notification\Data\PushMessageData;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

/** Standard Web Push (VAPID): works with every browser push service, no account needed. */
final class WebPushSender implements PushSender
{
    /** Messages live 12 h in the push service when the device is off. */
    private const TTL_SECONDS = 43_200;

    public function send(iterable $subscriptions, PushMessageData $message): array
    {
        $webPush = new WebPush(['VAPID' => [
            'subject' => (string) config('services.webpush.subject'),
            'publicKey' => (string) config('services.webpush.public_key'),
            'privateKey' => (string) config('services.webpush.private_key'),
        ]], ['TTL' => self::TTL_SECONDS]);

        $payload = (string) json_encode($message->toArray());
        foreach ($subscriptions as $subscription) {
            $webPush->queueNotification(Subscription::create([
                'endpoint' => $subscription->endpoint,
                'publicKey' => $subscription->public_key,
                'authToken' => $subscription->auth_token,
                'contentEncoding' => $subscription->content_encoding,
            ]), $payload);
        }

        $gone = [];
        foreach ($webPush->flush() as $report) {
            if ($report->isSubscriptionExpired()) {
                $gone[] = $report->getEndpoint();
            } elseif (! $report->isSuccess()) {
                Log::warning('notification_push_failed', ['reason' => $report->getReason()]);
            }
        }

        return $gone;
    }
}
