<?php

declare(strict_types=1);

namespace Domain\Team\Data;

use Spatie\LaravelData\Data;

/** A barber of the current unit who can move: active, with a login. */
final class MovableStaffData extends Data
{
    public function __construct(
        public readonly int $staff_id,
        public readonly int $tenant_id,
        public readonly int $user_id,
        public readonly string $name,
        public readonly ?string $avatar,
    ) {}
}
