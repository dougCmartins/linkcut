<?php

declare(strict_types=1);

namespace Domain\Finance\Actions;

use Domain\Finance\Data\CommissionIdData;
use Domain\Finance\Exceptions\CommissionNotFoundException;
use Domain\Finance\Models\CommissionAdjustment;
use Domain\Finance\Support\AssertOwner;
use Illuminate\Support\Facades\Log;

final class DeleteCommissionAdjustment
{
    public function handle(CommissionIdData $id): void
    {
        AssertOwner::handle();
        $row = CommissionAdjustment::query()->find($id->id) ?? throw new CommissionNotFoundException;
        if ($row->system_key !== null) {
            $row->update(['active' => false]);
            Log::info('finance_commission_system_adjustment_disabled', ['adjustment_id' => $row->id]);

            return;
        }
        $row->delete();
        Log::info('finance_commission_adjustment_deleted', ['adjustment_id' => $id->id]);
    }
}
