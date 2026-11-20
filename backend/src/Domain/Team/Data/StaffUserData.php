<?php

declare(strict_types=1);

namespace Domain\Team\Data;

use Spatie\LaravelData\Data;

/** A barber of the team and their login. */
final class StaffUserData extends Data
{
    public function __construct(
        public readonly int $staff_id,
        public readonly int $user_id,
    ) {}
}
