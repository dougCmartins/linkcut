<?php

declare(strict_types=1);

namespace Domain\Finance\Actions;

use Domain\Finance\Data\CommissionAdjustmentInputForCalcData;
use Domain\Finance\Models\CommissionAdjustment;

/** Active adjustments for a staff member on a date (tenant-wide + staff-specific). */
final class ListActiveCommissionAdjustments
{
    /**
     * @return list<CommissionAdjustmentInputForCalcData>
     */
    public function handle(int $staffId, string $onDate): array
    {
        return CommissionAdjustment::query()
            ->where('active', true)
            ->where('starts_on', '<=', $onDate)
            ->where(function ($q) use ($staffId): void {
                $q->whereNull('staff_id')->orWhere('staff_id', $staffId);
            })
            ->orderBy('id')
            ->get()
            ->map(fn (CommissionAdjustment $row): CommissionAdjustmentInputForCalcData => new CommissionAdjustmentInputForCalcData(
                name: $row->name,
                stage: $row->stage,
                kind: $row->kind,
                applies_to: $row->applies_to,
                value: (int) $row->value,
                system_key: $row->system_key,
            ))
            ->all();
    }
}
