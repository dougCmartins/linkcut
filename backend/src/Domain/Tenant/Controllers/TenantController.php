<?php

declare(strict_types=1);

namespace Domain\Tenant\Controllers;

use Domain\Tenant\Actions\ShowCurrentTenant;
use Domain\Tenant\Data\TenantData;

final class TenantController
{
    public function show(ShowCurrentTenant $action): TenantData
    {
        return $action->handle();
    }
}
