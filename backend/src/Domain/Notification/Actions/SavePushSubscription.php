<?php

declare(strict_types=1);

namespace Domain\Notification\Actions;

use Domain\Notification\Data\PushSubscriptionInputData;
use Domain\Notification\Models\PushSubscription;
use Domain\Notification\Support\PushEndpoints;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * This device of the signed-in barber starts receiving pushes. The endpoint is unique: subscribing again
 * (or another barber on the same browser) just moves it to the current user and tenant.
 */
final class SavePushSubscription
{
    public function handle(PushSubscriptionInputData $data): void
    {
        if (! PushEndpoints::isKnown($data->endpoint)) {
            throw ValidationException::withMessages(['endpoint' => 'url']);
        }
        PushSubscription::withoutTenantScope()->where('endpoint', $data->endpoint)->delete();
        PushSubscription::query()->create([
            'user_id' => (int) auth('api')->id(),
            'endpoint' => $data->endpoint,
            'public_key' => $data->keys->p256dh,
            'auth_token' => $data->keys->auth,
        ]);
        Log::info('notification_push_subscribed');
    }
}
