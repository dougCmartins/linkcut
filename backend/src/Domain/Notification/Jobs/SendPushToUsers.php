<?php

declare(strict_types=1);

namespace Domain\Notification\Jobs;

use Domain\Notification\Data\UsersPushData;
use Domain\Notification\Models\PushSubscription;
use Domain\Notification\Support\PushSender;
use Domain\Tenant\Context\CurrentTenant;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/** Sends in the queue to the given users' devices only. Gone devices are forgotten. */
final class SendPushToUsers implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 10;

    public function __construct(public readonly UsersPushData $data) {}

    public function handle(CurrentTenant $currentTenant, PushSender $sender): void
    {
        $userIds = array_values(array_unique(array_filter($this->data->user_ids)));
        if ($userIds === []) {
            return;
        }

        $currentTenant->run($this->data->tenant_id, function () use ($sender, $userIds): void {
            $subscriptions = PushSubscription::query()->whereIn('user_id', $userIds)->get();
            if ($subscriptions->isEmpty()) {
                return;
            }
            $gone = $sender->send($subscriptions, $this->data->message);
            PushSubscription::query()->whereIn('endpoint', $gone)->delete();
            Log::info('notification_push_sent', [
                'devices' => $subscriptions->count(),
                'gone' => count($gone),
                'tag' => $this->data->message->tag,
                'users' => count($userIds),
            ]);
        });
    }
}
