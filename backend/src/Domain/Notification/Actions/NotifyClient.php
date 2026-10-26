<?php

declare(strict_types=1);

namespace Domain\Notification\Actions;

use Domain\Notification\Data\ClientPushData;
use Domain\Notification\Jobs\SendPushToClient;

/** Queues a push for every device of a client (after the current transaction commits). */
final class NotifyClient
{
    public function handle(ClientPushData $data): void
    {
        SendPushToClient::dispatch($data)->afterCommit();
    }
}
