<?php

declare(strict_types=1);

namespace Domain\Finance\Actions;

use Domain\Finance\Data\CommissionAdjustmentData;
use Domain\Finance\Data\CommissionAdjustmentInputData;
use Domain\Finance\Enums\CommissionAppliesTo;
use Domain\Finance\Enums\CommissionKind;
use Domain\Finance\Enums\CommissionStage;
use Domain\Finance\Models\CommissionAdjustment;
use Domain\Finance\Support\AssertOwner;
use Domain\Finance\Support\CommissionAdjustmentMapper;
use Illuminate\Support\Facades\Log;

final class CreateCommissionAdjustment
{
    public function handle(CommissionAdjustmentInputData $data): CommissionAdjustmentData
    {
        AssertOwner::handle();
        $row = CommissionAdjustment::query()->create([
            'staff_id' => $data->staff_id,
            'name' => trim($data->name),
            'stage' => CommissionStage::from($data->stage),
            'kind' => CommissionKind::from($data->kind),
            'applies_to' => CommissionAppliesTo::from($data->applies_to),
            'value' => $data->value,
            'active' => $data->active,
            'starts_on' => $data->starts_on,
            'system_key' => null,
        ]);
        Log::info('finance_commission_adjustment_created', ['adjustment_id' => $row->id]);

        return CommissionAdjustmentMapper::toData($row);
    }
}
