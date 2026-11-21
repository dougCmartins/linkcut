<?php

declare(strict_types=1);

namespace Domain\Finance\Actions;

use Domain\Finance\Data\CommissionIdData;
use Domain\Finance\Exceptions\CommissionNotFoundException;
use Domain\Finance\Models\CommissionRule;
use Domain\Finance\Support\AssertOwner;
use Illuminate\Support\Facades\Log;

final class DeleteCommissionRule
{
    public function handle(CommissionIdData $id): void
    {
        AssertOwner::handle();
        $row = CommissionRule::query()->find($id->id) ?? throw new CommissionNotFoundException;
        $row->delete();
        Log::info('finance_commission_rule_deleted', ['rule_id' => $id->id]);
    }
}
