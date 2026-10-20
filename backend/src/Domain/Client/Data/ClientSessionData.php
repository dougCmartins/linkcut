<?php

declare(strict_types=1);

namespace Domain\Client\Data;

use Spatie\LaravelData\Data;

/** Short access token (Bearer, "client" guard) + opaque refresh token, lifetimes in seconds. */
final class ClientSessionData extends Data
{
    public function __construct(
        public readonly string $access_token,
        public readonly string $token_type,
        public readonly int $expires_in,
        public readonly string $refresh_token,
        public readonly int $refresh_expires_in,
        public readonly ClientData $client,
    ) {}
}
