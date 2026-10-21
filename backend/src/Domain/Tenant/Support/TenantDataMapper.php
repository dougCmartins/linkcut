<?php

declare(strict_types=1);

namespace Domain\Tenant\Support;

use Domain\Tenant\Data\TenantData;
use Domain\Tenant\Models\Tenant;

/** One place that turns a Tenant into the TenantData the API returns. */
final class TenantDataMapper
{
    public static function toData(Tenant $tenant): TenantData
    {
        return new TenantData(
            id: $tenant->id,
            name: $tenant->name,
            slug: $tenant->slug,
            active: $tenant->active,
            timezone: $tenant->timezone,
            onboarded: $tenant->onboarded_at !== null,
        );
    }
}
