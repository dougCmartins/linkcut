<?php

declare(strict_types=1);

namespace Domain\Identity\Data;

use Spatie\LaravelData\Data;

/** A barber of the team who accepted the invitation: the e-mail was proven by the invitation link. */
final class NewBarberData extends Data
{
    public function __construct(
        public readonly int $tenant_id,
        public readonly ?int $organization_id,
        public readonly string $name,
        public readonly string $email,
        public readonly string $password,
        public readonly string $terms_version,
    ) {}
}
