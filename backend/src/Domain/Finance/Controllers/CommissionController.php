<?php

declare(strict_types=1);

namespace Domain\Finance\Controllers;

use Domain\Finance\Actions\CloseCommissionPeriod;
use Domain\Finance\Actions\CreateCommissionAdjustment;
use Domain\Finance\Actions\CreateCommissionRule;
use Domain\Finance\Actions\DeleteCommissionAdjustment;
use Domain\Finance\Actions\DeleteCommissionRule;
use Domain\Finance\Actions\ListCommissionAdjustments;
use Domain\Finance\Actions\ListCommissionClosings;
use Domain\Finance\Actions\ListCommissionRules;
use Domain\Finance\Actions\ReopenCommissionPeriod;
use Domain\Finance\Actions\SimulateCommission;
use Domain\Finance\Actions\UpdateCommissionAdjustment;
use Domain\Finance\Data\CloseCommissionPeriodData;
use Domain\Finance\Data\CommissionAdjustmentData;
use Domain\Finance\Data\CommissionAdjustmentInputData;
use Domain\Finance\Data\CommissionCalculationData;
use Domain\Finance\Data\CommissionClosingData;
use Domain\Finance\Data\CommissionIdData;
use Domain\Finance\Data\CommissionRuleData;
use Domain\Finance\Data\CommissionRuleInputData;
use Domain\Finance\Data\SimulateCommissionData;
use Illuminate\Http\Response;

final class CommissionController
{
    /** @return list<CommissionRuleData> */
    public function rules(ListCommissionRules $action): array
    {
        return $action->handle();
    }

    public function storeRule(CommissionRuleInputData $data, CreateCommissionRule $action): CommissionRuleData
    {
        return $action->handle($data);
    }

    public function destroyRule(int $id, DeleteCommissionRule $action): Response
    {
        $action->handle(CommissionIdData::from(CommissionIdData::validate(['id' => $id])));

        return response()->noContent();
    }

    /** @return list<CommissionAdjustmentData> */
    public function adjustments(ListCommissionAdjustments $action): array
    {
        return $action->handle();
    }

    public function storeAdjustment(CommissionAdjustmentInputData $data, CreateCommissionAdjustment $action): CommissionAdjustmentData
    {
        return $action->handle($data);
    }

    public function updateAdjustment(int $id, CommissionAdjustmentInputData $data, UpdateCommissionAdjustment $action): CommissionAdjustmentData
    {
        return $action->handle(CommissionIdData::from(CommissionIdData::validate(['id' => $id])), $data);
    }

    public function destroyAdjustment(int $id, DeleteCommissionAdjustment $action): Response
    {
        $action->handle(CommissionIdData::from(CommissionIdData::validate(['id' => $id])));

        return response()->noContent();
    }

    public function simulate(SimulateCommissionData $data, SimulateCommission $action): CommissionCalculationData
    {
        return $action->handle($data);
    }

    /** @return list<CommissionClosingData> */
    public function closings(ListCommissionClosings $action): array
    {
        return $action->handle();
    }

    public function close(CloseCommissionPeriodData $data, CloseCommissionPeriod $action): CommissionClosingData
    {
        return $action->handle($data);
    }

    public function reopenClosing(int $id, ReopenCommissionPeriod $action): CommissionClosingData
    {
        return $action->handle(CommissionIdData::from(CommissionIdData::validate(['id' => $id])));
    }
}
