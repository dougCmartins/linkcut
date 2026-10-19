<?php

declare(strict_types=1);

namespace Domain\Tenant\Actions;

use Domain\Tenant\Context\CurrentTenant;
use Domain\Tenant\Data\TenantData;
use Domain\Tenant\Models\Tenant;

/** The barbershop of the signed-in barber (name, public slug, timezone). */
final class ShowCurrentTenant
{
    public function __construct(private readonly CurrentTenant $currentTenant) {}

    public function handle(): TenantData
    {
        $tenant = Tenant::query()->findOrFail($this->currentTenant->id());

        return new TenantData(
            id: $tenant->id,
            name: $tenant->name,
            slug: $tenant->slug,
            active: $tenant->active,
            timezone: $tenant->timezone,
        );
    }
}
