<?php

declare(strict_types=1);

namespace Domain\Notification\Data;

use Spatie\LaravelData\Data;

/** A browser subscription for a client (who is known by the session or by a signed link). */
final class ClientPushSubscriptionData extends Data
{
    public function __construct(
        public readonly int $client_id,
        public readonly PushSubscriptionInputData $subscription,
    ) {}
}
