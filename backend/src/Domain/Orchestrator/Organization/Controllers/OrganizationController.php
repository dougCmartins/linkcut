<?php

declare(strict_types=1);

namespace Domain\Orchestrator\Organization\Controllers;

use Domain\Orchestrator\Organization\Actions\CreateUnit;
use Domain\Orchestrator\Organization\Actions\ShowOrganization;
use Domain\Orchestrator\Organization\Data\CreateUnitData;
use Domain\Orchestrator\Organization\Data\OrganizationSummaryData;
use Illuminate\Http\JsonResponse;

final class OrganizationController
{
    public function show(ShowOrganization $action): OrganizationSummaryData
    {
        return $action->handle();
    }

    public function storeUnit(CreateUnitData $data, CreateUnit $action): JsonResponse
    {
        return response()->json($action->handle($data)->toArray(), 201);
    }
}
