<?php

declare(strict_types=1);

namespace Domain\Client\Data;

use Spatie\LaravelData\Data;

/** A client to receive the single "complete your sign-up" e-mail. */
final class ClientInviteData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $email,
        public readonly string $registration_url,
        public readonly string $opt_out_url,
    ) {}
}
