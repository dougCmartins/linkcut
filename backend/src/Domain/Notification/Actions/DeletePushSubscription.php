<?php

declare(strict_types=1);

namespace Domain\Notification\Actions;

use Domain\Notification\Data\PushEndpointData;
use Domain\Notification\Models\PushSubscription;
use Illuminate\Support\Facades\Log;

/** This device stops receiving (only a device of the signed-in barber). Unknown endpoints are ignored. */
final class DeletePushSubscription
{
    public function handle(PushEndpointData $data): void
    {
        PushSubscription::query()->where('user_id', auth('api')->id())->where('endpoint', $data->endpoint)->delete();
        Log::info('notification_push_unsubscribed');
    }
}
