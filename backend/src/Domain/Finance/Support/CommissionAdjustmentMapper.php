<?php

declare(strict_types=1);

namespace Domain\Finance\Support;

use Domain\Finance\Data\CommissionAdjustmentData;
use Domain\Finance\Models\CommissionAdjustment;

final class CommissionAdjustmentMapper
{
    public static function toData(CommissionAdjustment $row): CommissionAdjustmentData
    {
        return new CommissionAdjustmentData(
            id: $row->id,
            staff_id: $row->staff_id !== null ? (int) $row->staff_id : null,
            name: $row->name,
            stage: $row->stage->value,
            kind: $row->kind->value,
            applies_to: $row->applies_to->value,
            value: (int) $row->value,
            active: (bool) $row->active,
            starts_on: (string) $row->starts_on,
            system_key: $row->system_key,
        );
    }
}
