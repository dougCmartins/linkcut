<?php

declare(strict_types=1);

namespace Domain\Orchestrator\HistoryReport\Controllers;

use Domain\Finance\Data\PeriodData;
use Domain\Orchestrator\HistoryReport\Actions\RequestHistoryReport;
use Illuminate\Http\JsonResponse;

final class HistoryReportController
{
    /** 202: accepted, the e-mail comes in a moment. */
    public function store(PeriodData $data, RequestHistoryReport $action): JsonResponse
    {
        return response()->json($action->handle($data)->toArray(), 202);
    }
}
