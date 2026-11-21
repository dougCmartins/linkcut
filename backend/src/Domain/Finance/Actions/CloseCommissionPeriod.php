<?php

declare(strict_types=1);

namespace Domain\Finance\Actions;

use Domain\Finance\Data\CloseCommissionPeriodData;
use Domain\Finance\Data\CommissionClosingData;
use Domain\Finance\Models\CommissionClosing;
use Domain\Finance\Models\CommissionEntry;
use Domain\Finance\Support\AssertOwner;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

final class CloseCommissionPeriod
{
    public function handle(CloseCommissionPeriodData $data): CommissionClosingData
    {
        AssertOwner::handle();
        $userId = (int) Auth::id();

        $closing = CommissionClosing::query()->create([
            'from' => $data->from,
            'to' => $data->to,
            'closed_at' => now(),
            'closed_by' => $userId,
            'reopened_at' => null,
        ]);

        CommissionEntry::query()
            ->betweenDates($data->from, $data->to)
            ->whereNull('closing_id')
            ->update(['closing_id' => $closing->id]);

        Log::info('finance_commission_period_closed', [
            'closing_id' => $closing->id,
            'from' => $data->from,
            'to' => $data->to,
        ]);

        return new CommissionClosingData(
            id: $closing->id,
            from: (string) $closing->from,
            to: (string) $closing->to,
            closed_at: $closing->closed_at->toIso8601String(),
            closed_by: (int) $closing->closed_by,
            reopened_at: null,
        );
    }
}
