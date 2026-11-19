<?php

declare(strict_types=1);

namespace Domain\Team\Data;

use Spatie\LaravelData\Data;

/** A barber of the unit as "Minha equipe" lists them. `status`: invited (no login yet), active or inactive. */
final class StaffData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $email,
        public readonly string $status,
        public readonly bool $is_you,
        public readonly StaffPermissionsData $permissions,
    ) {}
}
