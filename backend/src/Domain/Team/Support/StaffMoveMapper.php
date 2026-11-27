<?php

declare(strict_types=1);

namespace Domain\Team\Support;

use Domain\Team\Data\StaffMoveData;
use Domain\Team\Models\StaffMove;

final class StaffMoveMapper
{
    public static function toData(StaffMove $move): StaffMoveData
    {
        return new StaffMoveData(
            id: $move->id,
            user_id: (int) $move->user_id,
            from_staff_id: (int) $move->from_staff_id,
            to_staff_id: (int) $move->to_staff_id,
            kind: $move->kind->value,
            starts_on: $move->starts_on->toDateString(),
            ends_on: $move->ends_on?->toDateString(),
            status: $move->status->value,
        );
    }
}
