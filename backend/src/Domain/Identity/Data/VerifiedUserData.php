<?php

declare(strict_types=1);

namespace Domain\Identity\Data;

use Spatie\LaravelData\Data;

/** The 2FA code was right: who signed in and from which tenant. */
final class VerifiedUserData extends Data
{
    public function __construct(
        public readonly int $user_id,
        public readonly int $tenant_id,
    ) {}
}
