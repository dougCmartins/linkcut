<?php

declare(strict_types=1);

namespace Domain\Notification\Jobs;

use Domain\Notification\Data\TenantPushData;
use Domain\Notification\Models\PushSubscription;
use Domain\Notification\Support\PushSender;
use Domain\Tenant\Context\CurrentTenant;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/** Sends in the queue, so the client's booking never waits for the push services. Gone devices are forgotten. */
final class SendPushToTenant implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 10;

    public function __construct(public readonly TenantPushData $data) {}

    public function handle(CurrentTenant $currentTenant, PushSender $sender): void
    {
        $currentTenant->run($this->data->tenant_id, function () use ($sender): void {
            $subscriptions = PushSubscription::query()->get();
            if ($subscriptions->isEmpty()) {
                return;
            }
            $gone = $sender->send($subscriptions, $this->data->message);
            PushSubscription::query()->whereIn('endpoint', $gone)->delete();
            Log::info('notification_push_sent', ['devices' => $subscriptions->count(), 'gone' => count($gone), 'tag' => $this->data->message->tag]);
        });
    }
}
