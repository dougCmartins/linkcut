<?php

declare(strict_types=1);

namespace Domain\Tenant\Actions;

use Domain\Tenant\Data\TenantData;
use Domain\Tenant\Data\TenantIdData;
use Domain\Tenant\Exceptions\TenantInactiveException;
use Domain\Tenant\Exceptions\TenantNotFoundException;
use Domain\Tenant\Models\Tenant;
use Domain\Tenant\Support\TenantDataMapper;

/** Refuses a missing or deactivated tenant (sign-in and every authenticated request). */
final class EnsureTenantIsActive
{
    public function handle(TenantIdData $data): TenantData
    {
        $tenant = Tenant::query()->find($data->tenant_id);
        if (! $tenant) {
            throw new TenantNotFoundException;
        }
        if (! $tenant->active) {
            throw new TenantInactiveException;
        }

        return TenantDataMapper::toData($tenant);
    }
}
