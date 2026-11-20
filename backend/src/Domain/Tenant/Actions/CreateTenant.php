<?php

declare(strict_types=1);

namespace Domain\Tenant\Actions;

use Domain\Tenant\Data\NewTenantData;
use Domain\Tenant\Data\TenantData;
use Domain\Tenant\Enums\OrganizationType;
use Domain\Tenant\Exceptions\SlugUnavailableException;
use Domain\Tenant\Models\Organization;
use Domain\Tenant\Models\Tenant;
use Domain\Tenant\Support\TenantDataMapper;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * A new barbershop, still in the onboarding wizard. Two sign-ups racing for one link: the second gets SLUG_UNAVAILABLE.
 * A sign-up also creates its individual organization (same name and slug); a branch joins the given organization,
 * which becomes multi-unit.
 */
final class CreateTenant
{
    public function handle(NewTenantData $data): TenantData
    {
        if (! CheckSlugAvailability::isFree($data->slug)) {
            throw new SlugUnavailableException;
        }
        try {
            $tenant = DB::transaction(fn (): Tenant => $data->organization_id === null ? $this->createMainUnit($data) : $this->createBranch($data));
        } catch (UniqueConstraintViolationException) {
            throw new SlugUnavailableException;
        }
        Log::info('tenant_created', ['tenant_id' => $tenant->id, 'organization_id' => $tenant->organization_id, 'is_main' => $tenant->is_main]);

        return TenantDataMapper::toData($tenant->refresh());
    }

    private function createMainUnit(NewTenantData $data): Tenant
    {
        $organization = Organization::query()->create(['type' => OrganizationType::Individual, 'name' => $data->name, 'slug' => $data->slug]);

        return Tenant::query()->create([...$this->columns($data), 'organization_id' => $organization->id, 'is_main' => true]);
    }

    private function createBranch(NewTenantData $data): Tenant
    {
        Organization::query()->whereKey($data->organization_id)->update(['type' => OrganizationType::MultiUnit]);

        return Tenant::query()->create([...$this->columns($data), 'organization_id' => $data->organization_id, 'is_main' => false]);
    }

    /** @return array<string, mixed> */
    private function columns(NewTenantData $data): array
    {
        return ['name' => $data->name, 'slug' => $data->slug, 'active' => true, 'onboarded_at' => null];
    }
}
