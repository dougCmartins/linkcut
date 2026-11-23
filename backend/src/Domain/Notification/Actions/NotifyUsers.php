<?php

declare(strict_types=1);

namespace Domain\Notification\Actions;

use Domain\Notification\Data\UsersPushData;
use Domain\Notification\Jobs\SendPushToUsers;

/** Queues a push for specific users of the unit (after the current transaction commits). */
final class NotifyUsers
{
    public function handle(UsersPushData $data): void
    {
        SendPushToUsers::dispatch($data)->afterCommit();
    }
}
