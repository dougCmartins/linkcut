<?php

declare(strict_types=1);

namespace Domain\Tenant\Actions;

use Domain\Tenant\Data\BrandData;
use Domain\Tenant\Data\BrandUnitData;
use Domain\Tenant\Data\TenantSlugData;
use Domain\Tenant\Enums\OrganizationType;
use Domain\Tenant\Exceptions\TenantNotFoundException;
use Domain\Tenant\Models\Organization;
use Domain\Tenant\Models\Tenant;

/**
 * The brand link (/{brand}): a brand with branches and its active units, main unit first.
 * A barber on their own, an unknown slug or a brand without an active unit look the same: 404.
 */
final class ShowBrand
{
    public function handle(TenantSlugData $data): BrandData
    {
        $organization = Organization::query()
            ->where('slug', $data->slug)
            ->where('type', OrganizationType::MultiUnit)
            ->first(['id', 'slug', 'name']);
        $units = $organization?->units()->where('active', true)->orderByDesc('is_main')->orderBy('name')->get(['id', 'slug', 'name', 'is_main']);
        if (! $organization || ! $units || $units->isEmpty()) {
            throw new TenantNotFoundException;
        }

        return new BrandData(
            slug: $organization->slug,
            name: $organization->name,
            units: $units->map(fn (Tenant $unit): BrandUnitData => new BrandUnitData(
                tenant_id: $unit->id,
                slug: $unit->slug,
                name: $unit->name,
                is_main: $unit->is_main,
            ))->values()->all(),
        );
    }
}
