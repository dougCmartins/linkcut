<?php

declare(strict_types=1);

namespace Domain\Notification\Data;

use Spatie\LaravelData\Data;

/** A push for specific users of a unit (barber + owner), not every device of the tenant. */
final class UsersPushData extends Data
{
    /**
     * @param  list<int>  $user_ids
     */
    public function __construct(
        public readonly int $tenant_id,
        public readonly array $user_ids,
        public readonly PushMessageData $message,
    ) {}
}
