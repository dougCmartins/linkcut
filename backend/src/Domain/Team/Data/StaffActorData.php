<?php

declare(strict_types=1);

namespace Domain\Team\Data;

use Spatie\LaravelData\Data;

/** Who is managing the team (the signed-in owner) and which barber they act on. */
final class StaffActorData extends Data
{
    public function __construct(
        public readonly int $user_id,
        public readonly ?int $staff_id = null,
    ) {}
}
