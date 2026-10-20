<?php

declare(strict_types=1);

namespace Domain\Schedule\Data;

use Spatie\LaravelData\Data;

final class ClientBookingsQueryData extends Data
{
    public function __construct(public readonly int $client_id) {}
}
