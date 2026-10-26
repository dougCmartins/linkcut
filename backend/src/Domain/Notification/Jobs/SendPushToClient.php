<?php

declare(strict_types=1);

namespace Domain\Notification\Jobs;

use Domain\Notification\Data\ClientPushData;
use Domain\Notification\Models\ClientPushSubscription;
use Domain\Notification\Support\PushSender;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/** Sends in the queue, so answering a booking never waits for the push services. Gone devices are forgotten. */
final class SendPushToClient implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 10;

    public function __construct(public readonly ClientPushData $data) {}

    public function handle(PushSender $sender): void
    {
        $subscriptions = ClientPushSubscription::query()->where('client_id', $this->data->client_id)->get();
        if ($subscriptions->isEmpty()) {
            return;
        }
        $gone = $sender->send($subscriptions, $this->data->message);
        ClientPushSubscription::query()->whereIn('endpoint', $gone)->delete();
        Log::info('notification_client_push_sent', ['devices' => $subscriptions->count(), 'gone' => count($gone), 'tag' => $this->data->message->tag]);
    }
}
