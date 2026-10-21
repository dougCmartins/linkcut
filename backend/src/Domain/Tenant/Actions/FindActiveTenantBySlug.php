<?php

declare(strict_types=1);

namespace Domain\Tenant\Actions;

use Domain\Tenant\Data\TenantData;
use Domain\Tenant\Data\TenantSlugData;
use Domain\Tenant\Exceptions\TenantNotFoundException;
use Domain\Tenant\Models\Tenant;
use Domain\Tenant\Support\TenantDataMapper;

/** Public pages: a missing or deactivated barbershop looks the same to visitors (404). */
final class FindActiveTenantBySlug
{
    public function handle(TenantSlugData $data): TenantData
    {
        $tenant = Tenant::query()->where('slug', $data->slug)->where('active', true)->first();
        if (! $tenant) {
            throw new TenantNotFoundException;
        }

        return TenantDataMapper::toData($tenant);
    }
}
