<?php

declare(strict_types=1);

namespace Domain\Identity\Data;

use Spatie\LaravelData\Data;

/** A barber and the unit they sign in to. */
final class UserUnitData extends Data
{
    public function __construct(
        public readonly int $user_id,
        public readonly int $tenant_id,
    ) {}
}
