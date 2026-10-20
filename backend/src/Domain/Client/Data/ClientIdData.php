<?php

declare(strict_types=1);

namespace Domain\Client\Data;

use Spatie\LaravelData\Data;

final class ClientIdData extends Data
{
    public function __construct(
        public readonly int $client_id,
        /** Keeps the refresh token family when the session is renewed. */
        public readonly ?string $family_id = null,
    ) {}
}
