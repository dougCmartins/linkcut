<?php

declare(strict_types=1);

namespace Domain\Tenant\Actions;

use Domain\Tenant\Data\TenantData;
use Domain\Tenant\Data\TenantIdData;
use Domain\Tenant\Data\UnitAccessData;
use Domain\Tenant\Exceptions\UnitForbiddenException;
use Domain\Tenant\Models\Tenant;

/**
 * The unit a request works on. Without a pick, the user's own unit (the main one, for the owner).
 * Another unit only for the owner of the organization, and only one of its units: anything else is 403.
 */
final class ResolveActiveUnit
{
    public function __construct(private readonly EnsureTenantIsActive $ensureActive) {}

    public function handle(UnitAccessData $data): TenantData
    {
        if ($data->unit_id === null || $data->unit_id === $data->home_tenant_id) {
            return $this->ensureActive->handle(new TenantIdData(tenant_id: $data->home_tenant_id));
        }

        $unit = Tenant::query()->with('organization:id,owner_user_id')->find($data->unit_id);
        $isOwnersUnit = $unit !== null
            && $data->organization_id !== null
            && $unit->organization_id === $data->organization_id
            && $unit->organization?->owner_user_id === $data->user_id;
        if (! $isOwnersUnit) {
            throw new UnitForbiddenException;
        }

        return $this->ensureActive->handle(new TenantIdData(tenant_id: $unit->id));
    }
}
