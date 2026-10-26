<?php

declare(strict_types=1);

namespace Domain\Notification\Data;

use Spatie\LaravelData\Data;

final class ClientPushData extends Data
{
    public function __construct(
        public readonly int $client_id,
        public readonly PushMessageData $message,
    ) {}
}
