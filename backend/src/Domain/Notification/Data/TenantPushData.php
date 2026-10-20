<?php

declare(strict_types=1);

namespace Domain\Notification\Data;

use Spatie\LaravelData\Data;

/** A message for every barber (device) of a barbershop. */
final class TenantPushData extends Data
{
    public function __construct(
        public readonly int $tenant_id,
        public readonly PushMessageData $message,
    ) {}
}
