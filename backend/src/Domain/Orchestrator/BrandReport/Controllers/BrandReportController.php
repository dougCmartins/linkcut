<?php

declare(strict_types=1);

namespace Domain\Orchestrator\BrandReport\Controllers;

use Domain\Finance\Data\PeriodData;
use Domain\Orchestrator\BrandReport\Actions\RequestBrandReport;
use Domain\Orchestrator\BrandReport\Actions\ShowBrandReport;
use Domain\Orchestrator\BrandReport\Data\BrandReportData;
use Illuminate\Http\JsonResponse;

final class BrandReportController
{
    public function show(PeriodData $period, ShowBrandReport $action): BrandReportData
    {
        return $action->handle($period);
    }

    /** 202: accepted, the e-mail comes in a moment. */
    public function store(PeriodData $period, RequestBrandReport $action): JsonResponse
    {
        return response()->json($action->handle($period)->toArray(), 202);
    }
}
