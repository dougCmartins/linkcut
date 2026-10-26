<?php

declare(strict_types=1);

namespace Domain\Notification\Actions;

use Domain\Notification\Data\ClientPushSubscriptionData;
use Domain\Notification\Models\ClientPushSubscription;
use Domain\Notification\Support\PushEndpoints;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/** This device of a client starts getting the barbershop's answers. Subscribing again moves it to this client. */
final class SaveClientPushSubscription
{
    public function handle(ClientPushSubscriptionData $data): void
    {
        $subscription = $data->subscription;
        if (! PushEndpoints::isKnown($subscription->endpoint)) {
            throw ValidationException::withMessages(['endpoint' => 'url']);
        }

        ClientPushSubscription::query()->updateOrCreate(['endpoint' => $subscription->endpoint], [
            'client_id' => $data->client_id,
            'public_key' => $subscription->keys->p256dh,
            'auth_token' => $subscription->keys->auth,
        ]);
        Log::info('notification_client_push_subscribed', ['client_id' => $data->client_id]);
    }
}
