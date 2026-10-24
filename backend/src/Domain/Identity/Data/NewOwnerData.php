<?php

declare(strict_types=1);

namespace Domain\Identity\Data;

use Spatie\LaravelData\Data;

/** The owner of a new barbershop, with the e-mail already confirmed by a code. */
final class NewOwnerData extends Data
{
    public function __construct(
        public readonly int $tenant_id,
        public readonly string $name,
        public readonly string $email,
        public readonly string $password,
        /** Version of the Terms of Use and Privacy Policy the owner accepted (config legal.terms_version). */
        public readonly string $terms_version,
    ) {}
}
