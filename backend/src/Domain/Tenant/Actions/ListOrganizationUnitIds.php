<?php

declare(strict_types=1);

namespace Domain\Tenant\Actions;

use Domain\Tenant\Data\TenantIdData;
use Domain\Tenant\Data\TenantIdsData;
use Domain\Tenant\Models\Tenant;

/** Every unit of the organization of this tenant (itself included), e.g. to delete a whole brand. */
final class ListOrganizationUnitIds
{
    public function handle(TenantIdData $data): TenantIdsData
    {
        $organizationId = Tenant::query()->whereKey($data->tenant_id)->value('organization_id');
        $ids = $organizationId === null
            ? [$data->tenant_id]
            : Tenant::query()->where('organization_id', $organizationId)->orderBy('id')->pluck('id')->all();

        return new TenantIdsData(ids: array_map('intval', $ids));
    }
}
