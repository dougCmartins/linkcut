<?php

declare(strict_types=1);

namespace Domain\Tenant\Controllers;

use Domain\Tenant\Actions\ListUnits;
use Domain\Tenant\Data\UnitAccessData;
use Domain\Tenant\Data\UnitListData;
use Illuminate\Http\Request;

final class UnitController
{
    public function index(Request $request, ListUnits $action): UnitListData
    {
        $user = $request->user();
        $organizationId = $user->getAttribute('organization_id');

        return $action->handle(new UnitAccessData(
            user_id: (int) $user->getAuthIdentifier(),
            home_tenant_id: (int) $user->getAttribute('tenant_id'),
            organization_id: $organizationId === null ? null : (int) $organizationId,
        ));
    }
}
