<?php

declare(strict_types=1);

namespace Domain\Finance\Actions;

use Domain\Finance\Data\CommissionAdjustmentData;
use Domain\Finance\Data\CommissionAdjustmentInputData;
use Domain\Finance\Data\CommissionIdData;
use Domain\Finance\Enums\CommissionAppliesTo;
use Domain\Finance\Enums\CommissionKind;
use Domain\Finance\Enums\CommissionStage;
use Domain\Finance\Exceptions\CommissionNotFoundException;
use Domain\Finance\Models\CommissionAdjustment;
use Domain\Finance\Support\AssertOwner;
use Domain\Finance\Support\CommissionAdjustmentMapper;
use Illuminate\Support\Facades\Log;

final class UpdateCommissionAdjustment
{
    public function handle(CommissionIdData $id, CommissionAdjustmentInputData $data): CommissionAdjustmentData
    {
        AssertOwner::handle();
        $row = CommissionAdjustment::query()->find($id->id) ?? throw new CommissionNotFoundException;

        $payload = [
            'active' => $data->active,
            'starts_on' => $data->starts_on,
        ];
        // System rows: only toggle active / starts_on; keep name/stage/kind.
        if ($row->system_key === null) {
            $payload = [
                ...$payload,
                'staff_id' => $data->staff_id,
                'name' => trim($data->name),
                'stage' => CommissionStage::from($data->stage),
                'kind' => CommissionKind::from($data->kind),
                'applies_to' => CommissionAppliesTo::from($data->applies_to),
                'value' => $data->value,
            ];
        }

        $row->update($payload);
        Log::info('finance_commission_adjustment_updated', ['adjustment_id' => $row->id]);

        return CommissionAdjustmentMapper::toData($row->fresh());
    }
}
