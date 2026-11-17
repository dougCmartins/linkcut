<?php

declare(strict_types=1);

namespace Domain\Tenant\Actions;

use Domain\Tenant\Data\UnitAccessData;
use Domain\Tenant\Data\UnitData;
use Domain\Tenant\Data\UnitListData;
use Domain\Tenant\Exceptions\TenantNotFoundException;
use Domain\Tenant\Models\Organization;
use Domain\Tenant\Models\Tenant;

/** The unit picker: every unit of the organization for its owner; only their own unit for anybody else. */
final class ListUnits
{
    public function handle(UnitAccessData $data): UnitListData
    {
        $home = Tenant::query()->find($data->home_tenant_id) ?? throw new TenantNotFoundException;
        $organization = Organization::query()->find($home->organization_id) ?? throw new TenantNotFoundException;
        $isOwner = $organization->owner_user_id === $data->user_id;

        $units = $isOwner
            ? $organization->units()->orderByDesc('is_main')->orderBy('name')->get()
            : collect([$home]);

        return new UnitListData(
            organization_id: $organization->id,
            organization_name: $organization->name,
            organization_type: $organization->type,
            can_manage: $isOwner,
            units: $units->map(fn (Tenant $unit): UnitData => new UnitData(
                id: $unit->id,
                name: $unit->name,
                slug: $unit->slug,
                is_main: $unit->is_main,
                active: $unit->active,
                onboarded: $unit->onboarded_at !== null,
            ))->values()->all(),
        );
    }
}
