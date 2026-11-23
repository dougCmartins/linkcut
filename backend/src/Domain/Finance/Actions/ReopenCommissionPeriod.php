<?php

declare(strict_types=1);

namespace Domain\Finance\Actions;

use Domain\Finance\Data\CommissionClosingData;
use Domain\Finance\Data\CommissionIdData;
use Domain\Finance\Exceptions\CommissionNotFoundException;
use Domain\Finance\Models\CommissionClosing;
use Domain\Finance\Models\CommissionEntry;
use Domain\Finance\Support\AssertOwner;
use Illuminate\Support\Facades\Log;

final class ReopenCommissionPeriod
{
    public function handle(CommissionIdData $id): CommissionClosingData
    {
        AssertOwner::handle();
        $closing = CommissionClosing::query()->find($id->id) ?? throw new CommissionNotFoundException;
        if ($closing->reopened_at === null) {
            $closing->update(['reopened_at' => now()]);
            CommissionEntry::query()->where('closing_id', $closing->id)->update(['closing_id' => null]);
            Log::info('finance_commission_period_reopened', ['closing_id' => $closing->id]);
        }

        $closing->refresh();

        return new CommissionClosingData(
            id: $closing->id,
            from: (string) $closing->from,
            to: (string) $closing->to,
            closed_at: $closing->closed_at->toIso8601String(),
            closed_by: (int) $closing->closed_by,
            reopened_at: $closing->reopened_at?->toIso8601String(),
        );
    }
}
