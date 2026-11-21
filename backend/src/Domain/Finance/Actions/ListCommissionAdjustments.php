<?php

declare(strict_types=1);

namespace Domain\Finance\Actions;

use Domain\Finance\Data\CommissionAdjustmentData;
use Domain\Finance\Models\CommissionAdjustment;
use Domain\Finance\Support\AssertOwner;
use Domain\Finance\Support\CommissionAdjustmentMapper;

final class ListCommissionAdjustments
{
    public function __construct(private readonly EnsureServiceCostAdjustment $ensureServiceCost) {}

    /** @return list<CommissionAdjustmentData> */
    public function handle(): array
    {
        AssertOwner::handle();
        $this->ensureServiceCost->handle();

        return CommissionAdjustment::query()
            ->orderBy('id')
            ->get()
            ->map(fn (CommissionAdjustment $row): CommissionAdjustmentData => CommissionAdjustmentMapper::toData($row))
            ->all();
    }
}
