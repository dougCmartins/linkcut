<?php

declare(strict_types=1);

namespace Domain\Notification\Actions;

use Domain\Notification\Data\PushSubscriptionInputData;
use Domain\Notification\Models\PushSubscription;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * This device of the signed-in barber starts receiving pushes. The endpoint is unique: subscribing again
 * (or another barber on the same browser) just moves it to the current user and tenant.
 */
final class SavePushSubscription
{
    /** Chrome/Edge (FCM), Firefox, Safari and Windows push services. */
    private const PUSH_HOSTS = ['fcm.googleapis.com', 'updates.push.services.mozilla.com', 'web.push.apple.com', '.notify.windows.com', '.push.apple.com'];

    public function handle(PushSubscriptionInputData $data): void
    {
        if (! self::isKnownPushService($data->endpoint)) {
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

    private static function isKnownPushService(string $endpoint): bool
    {
        $host = mb_strtolower((string) parse_url($endpoint, PHP_URL_HOST));

        return collect(self::PUSH_HOSTS)->contains(
            fn (string $allowed): bool => str_starts_with($allowed, '.') ? str_ends_with($host, $allowed) : $host === $allowed,
        );
    }
}
