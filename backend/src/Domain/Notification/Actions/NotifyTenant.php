<?php

declare(strict_types=1);

namespace Domain\Notification\Actions;

use Domain\Notification\Data\TenantPushData;
use Domain\Notification\Jobs\SendPushToTenant;

/** Queues a push for every device of the barbershop (after the current transaction commits). */
final class NotifyTenant
{
    public function handle(TenantPushData $data): void
    {
        SendPushToTenant::dispatch($data)->afterCommit();
    }
}
