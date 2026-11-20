<?php

declare(strict_types=1);

namespace Domain\Orchestrator\Units\Controllers;

use Domain\Orchestrator\Units\Actions\CreateUnit;
use Domain\Orchestrator\Units\Data\NewUnitData;
use Domain\Tenant\Data\UnitAccessData;
use Domain\Tenant\Data\UnitData;
use Illuminate\Http\Request;

final class UnitController
{
    public function store(Request $request, NewUnitData $data, CreateUnit $action): UnitData
    {
        $user = $request->user();
        $organizationId = $user->getAttribute('organization_id');

        return $action->handle(new UnitAccessData(
            user_id: (int) $user->getAuthIdentifier(),
            home_tenant_id: (int) $user->getAttribute('tenant_id'),
            organization_id: $organizationId === null ? null : (int) $organizationId,
        ), $data);
    }
}
