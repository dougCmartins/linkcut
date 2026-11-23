<?php

declare(strict_types=1);

namespace Domain\Finance\Actions;

use Domain\Finance\Data\CommissionCalculationData;
use Domain\Finance\Data\SimulateCommissionData;
use Domain\Finance\Support\AssertOwner;
use Domain\Finance\Support\CommissionCalculator;

final class SimulateCommission
{
    public function __construct(
        private readonly ListActiveCommissionAdjustments $listAdjustments,
        private readonly EnsureServiceCostAdjustment $ensureServiceCost,
        private readonly CommissionCalculator $calculator,
    ) {}

    public function handle(SimulateCommissionData $data): CommissionCalculationData
    {
        AssertOwner::handle();
        $this->ensureServiceCost->handle();
        $adjustments = $data->use_saved_adjustments
            ? $this->listAdjustments->handle($data->staff_id, $data->date)
            : [];

        return $this->calculator->calculate(
            $data->price_in_cents,
            $data->percent,
            $adjustments,
            $data->cost_in_cents,
        );
    }
}
