<?php

declare(strict_types=1);

namespace Domain\Team\Data;

use Spatie\LaravelData\Data;

/** A move as the API returns it. */
final class StaffMoveData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly int $user_id,
        public readonly int $from_staff_id,
        public readonly int $to_staff_id,
        public readonly string $kind,
        public readonly string $starts_on,
        public readonly ?string $ends_on,
        public readonly string $status,
    ) {}
}
