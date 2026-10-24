<?php

declare(strict_types=1);

namespace Domain\Schedule\Data;

use Spatie\LaravelData\Data;

final class ClientBookingsQueryData extends Data
{
    public function __construct(
        public readonly int $client_id,
        /** Also the bookings made with this e-mail before the client had an account (client_id empty). */
        public readonly ?string $email = null,
    ) {}
}
