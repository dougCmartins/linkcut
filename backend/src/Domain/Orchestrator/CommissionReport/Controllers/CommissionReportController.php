<?php

declare(strict_types=1);

namespace Domain\Orchestrator\CommissionReport\Controllers;

use Domain\Finance\Data\CommissionReportData;
use Domain\Finance\Data\CommissionReportQueryData;
use Domain\Orchestrator\CommissionReport\Actions\PresentCommissionReport;
use Domain\Orchestrator\CommissionReport\Actions\RequestCommissionReport;
use Domain\Orchestrator\CommissionReport\Data\CommissionReportRequestedData;

final class CommissionReportController
{
    public function index(CommissionReportQueryData $query, PresentCommissionReport $action): CommissionReportData
    {
        return $action->handle($query);
    }

    public function store(CommissionReportQueryData $query, RequestCommissionReport $action): CommissionReportRequestedData
    {
        return $action->handle($query);
    }
}
