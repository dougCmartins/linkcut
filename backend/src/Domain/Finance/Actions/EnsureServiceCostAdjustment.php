<?php

declare(strict_types=1);

namespace Domain\Finance\Actions;

use Domain\Finance\Data\CommissionAdjustmentData;
use Domain\Finance\Enums\CommissionAppliesTo;
use Domain\Finance\Enums\CommissionKind;
use Domain\Finance\Enums\CommissionStage;
use Domain\Finance\Models\CommissionAdjustment;
use Domain\Finance\Support\CommissionAdjustmentMapper;

/** Ensures the system "service cost" adjustment exists (inactive by default). */
final class EnsureServiceCostAdjustment
{
    public function handle(): CommissionAdjustmentData
    {
        $row = CommissionAdjustment::query()->firstOrCreate(
            ['system_key' => CommissionAdjustment::SYSTEM_SERVICE_COST],
            [
                'staff_id' => null,
                'name' => 'Custo do serviço',
                'stage' => CommissionStage::Before,
                'kind' => CommissionKind::Fixed,
                'applies_to' => CommissionAppliesTo::Price,
                'value' => 0,
                'active' => false,
                'starts_on' => '2000-01-01',
            ],
        );

        return CommissionAdjustmentMapper::toData($row);
    }
}
