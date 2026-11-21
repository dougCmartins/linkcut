<?php

declare(strict_types=1);

namespace Domain\Finance\Actions;

use Domain\Finance\Data\CommissionClosingData;
use Domain\Finance\Models\CommissionClosing;
use Domain\Finance\Support\AssertOwner;

final class ListCommissionClosings
{
    /** @return list<CommissionClosingData> */
    public function handle(): array
    {
        AssertOwner::handle();

        return CommissionClosing::query()
            ->orderByDesc('from')
            ->orderByDesc('id')
            ->get()
            ->map(fn (CommissionClosing $row): CommissionClosingData => new CommissionClosingData(
                id: $row->id,
                from: (string) $row->from,
                to: (string) $row->to,
                closed_at: $row->closed_at->toIso8601String(),
                closed_by: (int) $row->closed_by,
                reopened_at: $row->reopened_at?->toIso8601String(),
            ))
            ->all();
    }
}
