<?php

declare(strict_types=1);

namespace Domain\Orchestrator\Organization\Actions;

use Domain\Orchestrator\Organization\Data\OrganizationSummaryData;
use Domain\Orchestrator\Organization\Data\UnitData;
use Domain\Tenant\Exceptions\TenantNotResolvedException;
use Domain\Tenant\Models\Organization;
use Domain\Tenant\Models\Tenant;
use Illuminate\Support\Facades\Auth;

final class ShowOrganization
{
    public function handle(): OrganizationSummaryData
    {
        $organizationId = Auth::user()?->organization_id;
        if (! $organizationId) {
            throw new TenantNotResolvedException;
        }

        $organization = Organization::query()->findOrFail($organizationId);
        $units = Tenant::query()
            ->where('organization_id', $organization->id)
            ->orderByDesc('is_main')
            ->orderBy('id')
            ->get(['id', 'name', 'slug', 'is_main'])
            ->map(fn (Tenant $tenant): UnitData => new UnitData(
                id: $tenant->id,
                name: $tenant->name,
                slug: $tenant->slug,
                is_main: $tenant->is_main,
            ))
            ->values()
            ->all();

        return new OrganizationSummaryData(
            id: $organization->id,
            type: $organization->type,
            name: $organization->name,
            slug: $organization->slug,
            units: $units,
        );
    }
}
