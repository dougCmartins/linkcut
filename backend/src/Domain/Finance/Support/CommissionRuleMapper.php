<?php

declare(strict_types=1);

namespace Domain\Finance\Support;

use Domain\Finance\Data\CommissionRuleData;
use Domain\Finance\Models\CommissionRule;

final class CommissionRuleMapper
{
    public static function toData(CommissionRule $row): CommissionRuleData
    {
        return new CommissionRuleData(
            id: $row->id,
            service_id: (int) $row->service_id,
            staff_id: $row->staff_id !== null ? (int) $row->staff_id : null,
            percent: (int) $row->percent,
            starts_on: (string) $row->starts_on,
        );
    }
}
