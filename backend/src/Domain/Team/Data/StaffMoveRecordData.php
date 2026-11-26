<?php

declare(strict_types=1);

namespace Domain\Team\Data;

use Domain\Team\Enums\StaffMoveKind;
use Spatie\LaravelData\Data;

final class StaffMoveRecordData extends Data
{
    public function __construct(
        public readonly int $organization_id,
        public readonly int $user_id,
        public readonly int $from_staff_id,
        public readonly int $to_staff_id,
        public readonly StaffMoveKind $kind,
        public readonly string $starts_on,
        public readonly ?string $ends_on,
        public readonly int $created_by,
    ) {}
}
