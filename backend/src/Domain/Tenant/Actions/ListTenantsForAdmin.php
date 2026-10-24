<?php

declare(strict_types=1);

namespace Domain\Tenant\Actions;

use Domain\Tenant\Data\TenantData;
use Domain\Tenant\Models\Tenant;
use Domain\Tenant\Support\TenantDataMapper;
use Spatie\LaravelData\DataCollection;

/** Every barbershop, newest first (platform operator only: artisan tenant:list). */
final class ListTenantsForAdmin
{
    /** @return DataCollection<int, TenantData> */
    public function handle(): DataCollection
    {
        $tenants = Tenant::query()->select(Tenant::DATA_COLUMNS)->orderByDesc('id')->get()
            ->map(fn (Tenant $tenant): TenantData => TenantDataMapper::toData($tenant));

        return TenantData::collect($tenants, DataCollection::class);
    }
}
