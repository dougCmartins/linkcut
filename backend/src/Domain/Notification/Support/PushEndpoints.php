<?php

declare(strict_types=1);

namespace Domain\Notification\Support;

/** Only the browsers' push services may receive our pushes (never an address chosen by someone else). */
final class PushEndpoints
{
    /** Chrome/Edge (FCM), Firefox, Safari and Windows push services. */
    private const HOSTS = ['fcm.googleapis.com', 'updates.push.services.mozilla.com', 'web.push.apple.com', '.notify.windows.com', '.push.apple.com'];

    public static function isKnown(string $endpoint): bool
    {
        $host = mb_strtolower((string) parse_url($endpoint, PHP_URL_HOST));

        return collect(self::HOSTS)->contains(
            fn (string $allowed): bool => str_starts_with($allowed, '.') ? str_ends_with($host, $allowed) : $host === $allowed,
        );
    }
}
