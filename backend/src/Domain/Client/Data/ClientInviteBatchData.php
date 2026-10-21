<?php

declare(strict_types=1);

namespace Domain\Client\Data;

use Spatie\LaravelData\Data;

/** How many clients to invite per round (bounded memory, whatever the number of clients). */
final class ClientInviteBatchData extends Data
{
    public function __construct(public readonly int $limit = 500) {}
}
