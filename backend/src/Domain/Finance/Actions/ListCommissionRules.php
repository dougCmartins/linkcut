<?php

declare(strict_types=1);

namespace Domain\Finance\Actions;

use Domain\Finance\Data\CommissionRuleData;
use Domain\Finance\Models\CommissionRule;
use Domain\Finance\Support\AssertOwner;
use Domain\Finance\Support\CommissionRuleMapper;

final class ListCommissionRules
{
    /** @return list<CommissionRuleData> */
    public function handle(): array
    {
        AssertOwner::handle();

        return CommissionRule::query()
            ->orderBy('service_id')
            ->orderByDesc('starts_on')
            ->get()
            ->map(fn (CommissionRule $row): CommissionRuleData => CommissionRuleMapper::toData($row))
            ->all();
    }
}
