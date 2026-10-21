<?php

declare(strict_types=1);

namespace Domain\Tenant\Actions;

use Domain\Tenant\Data\NewTenantData;
use Domain\Tenant\Data\TenantData;
use Domain\Tenant\Exceptions\SlugUnavailableException;
use Domain\Tenant\Models\Tenant;
use Domain\Tenant\Support\TenantDataMapper;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Log;

/** A new barbershop, still in the onboarding wizard. Two sign-ups racing for one link: the second gets SLUG_UNAVAILABLE. */
final class CreateTenant
{
    public function handle(NewTenantData $data): TenantData
    {
        if (! CheckSlugAvailability::isFree($data->slug)) {
            throw new SlugUnavailableException;
        }
        try {
            $tenant = Tenant::query()->create(['name' => $data->name, 'slug' => $data->slug, 'active' => true, 'onboarded_at' => null]);
        } catch (UniqueConstraintViolationException) {
            throw new SlugUnavailableException;
        }
        Log::info('tenant_created', ['tenant_id' => $tenant->id]);

        return TenantDataMapper::toData($tenant->refresh());
    }
}
