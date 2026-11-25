<?php

declare(strict_types=1);

namespace Domain\Finance\Actions;

use Domain\Finance\Data\CommissionRuleData;
use Domain\Finance\Data\CommissionRuleInputData;
use Domain\Finance\Models\CommissionRule;
use Domain\Finance\Support\AssertOwner;
use Domain\Finance\Support\CommissionRuleMapper;
use Illuminate\Support\Facades\Log;

final class CreateCommissionRule
{
    public function handle(CommissionRuleInputData $data): CommissionRuleData
    {
        AssertOwner::handle();
        $row = CommissionRule::query()->create([
            'service_id' => $data->service_id,
            'staff_id' => $data->staff_id,
            'percent' => $data->percent,
            'starts_on' => $data->starts_on,
        ]);
        Log::info('finance_commission_rule_created', ['rule_id' => $row->id]);

        return CommissionRuleMapper::toData($row);
    }
}
