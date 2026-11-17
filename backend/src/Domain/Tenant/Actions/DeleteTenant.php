<?php

declare(strict_types=1);

namespace Domain\Tenant\Actions;

use Domain\Tenant\Data\TenantIdData;
use Domain\Tenant\Models\Organization;
use Domain\Tenant\Models\Tenant;

/**
 * Deletes the barbershop row: settings, images, bookings, finance and push subscriptions go by cascade.
 * The organization goes with its last unit.
 */
final class DeleteTenant
{
    public function handle(TenantIdData $data): void
    {
        $organizationId = Tenant::query()->whereKey($data->tenant_id)->value('organization_id');
        Tenant::query()->whereKey($data->tenant_id)->delete();
        if ($organizationId !== null && ! Tenant::query()->where('organization_id', $organizationId)->exists()) {
            Organization::query()->whereKey($organizationId)->delete();
        }
    }
}
