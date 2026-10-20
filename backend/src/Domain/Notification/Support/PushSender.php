<?php

declare(strict_types=1);

namespace Domain\Notification\Support;

use Domain\Notification\Data\PushMessageData;
use Domain\Notification\Models\PushSubscription;

/** Delivers a push message to devices. Swappable, so tests never call a real push service. */
interface PushSender
{
    /**
     * @param  iterable<PushSubscription>  $subscriptions
     * @return list<string> endpoints that no longer exist (the device unsubscribed or expired)
     */
    public function send(iterable $subscriptions, PushMessageData $message): array;
}
