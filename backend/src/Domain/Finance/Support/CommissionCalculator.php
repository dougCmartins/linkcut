<?php

declare(strict_types=1);

namespace Domain\Finance\Support;

use Domain\Finance\Data\CommissionAdjustmentInputForCalcData;
use Domain\Finance\Data\CommissionAdjustmentLineData;
use Domain\Finance\Data\CommissionCalculationData;
use Domain\Finance\Enums\CommissionAppliesTo;
use Domain\Finance\Enums\CommissionKind;
use Domain\Finance\Enums\CommissionStage;
use Domain\Finance\Models\CommissionAdjustment;

/**
 * Pure commission math in integer cents.
 * Flow: base = price − before; commission = base × percent; amount = commission − after.
 * Only the final amount is rounded (half up). Percent is in tenths (50% = 500).
 */
final class CommissionCalculator
{
    /**
     * @param  list<CommissionAdjustmentInputForCalcData>  $adjustments
     */
    public function calculate(
        int $priceInCents,
        int $percentTenths,
        array $adjustments,
        int $serviceCostInCents = 0,
    ): CommissionCalculationData {
        $beforeLines = [];
        $beforeDeduction = 0;

        foreach ($adjustments as $adj) {
            if ($adj->stage !== CommissionStage::Before) {
                continue;
            }
            $deducted = $this->deductionAmount($adj, $priceInCents, commissionSoFar: 0, serviceCostInCents: $serviceCostInCents);
            $beforeDeduction += $deducted;
            $beforeLines[] = new CommissionAdjustmentLineData(
                name: $adj->name,
                stage: $adj->stage->value,
                kind: $adj->kind->value,
                applies_to: $adj->applies_to->value,
                amount_in_cents: (int) round($deducted, 0, PHP_ROUND_HALF_UP),
            );
        }

        $baseExact = max(0.0, $priceInCents - $beforeDeduction);
        // Exact commission before after-adjustments (no round yet).
        $commissionExact = $baseExact * $percentTenths / 1000;

        $afterLines = [];
        $afterDeduction = 0.0;
        foreach ($adjustments as $adj) {
            if ($adj->stage !== CommissionStage::After) {
                continue;
            }
            $deducted = $this->deductionAmount($adj, $priceInCents, commissionSoFar: $commissionExact, serviceCostInCents: $serviceCostInCents);
            $afterDeduction += $deducted;
            $afterLines[] = new CommissionAdjustmentLineData(
                name: $adj->name,
                stage: $adj->stage->value,
                kind: $adj->kind->value,
                applies_to: $adj->applies_to->value,
                amount_in_cents: (int) round($deducted, 0, PHP_ROUND_HALF_UP),
            );
        }

        $finalExact = max(0.0, $commissionExact - $afterDeduction);

        return new CommissionCalculationData(
            base_in_cents: (int) round($baseExact, 0, PHP_ROUND_HALF_UP),
            percent: $percentTenths,
            amount_in_cents: (int) round($finalExact, 0, PHP_ROUND_HALF_UP),
            adjustments: [...$beforeLines, ...$afterLines],
        );
    }

    private function deductionAmount(
        CommissionAdjustmentInputForCalcData $adj,
        int $priceInCents,
        float $commissionSoFar,
        int $serviceCostInCents,
    ): float {
        if ($adj->system_key === CommissionAdjustment::SYSTEM_SERVICE_COST) {
            return (float) max(0, $serviceCostInCents);
        }

        return match ($adj->kind) {
            CommissionKind::Fixed => (float) $adj->value,
            CommissionKind::Percent => match ($adj->applies_to) {
                CommissionAppliesTo::Price, CommissionAppliesTo::Appointment => $priceInCents * $adj->value / 1000,
                CommissionAppliesTo::Commission => $commissionSoFar * $adj->value / 1000,
            },
        };
    }
}
