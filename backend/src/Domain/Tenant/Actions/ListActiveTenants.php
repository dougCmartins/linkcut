<?php

declare(strict_types=1);

namespace Domain\Tenant\Actions;

use Domain\Tenant\Data\TenantData;
use Domain\Tenant\Models\Tenant;
use Domain\Tenant\Support\TenantDataMapper;
use Spatie\LaravelData\DataCollection;

/** Scheduled tasks run once per active barbershop. */
final class ListActiveTenants
{
    /** @return DataCollection<int, TenantData> */
    public function handle(): DataCollection
    {
        $tenants = Tenant::query()->where('active', true)->orderBy('id')->get()
            ->map(fn (Tenant $tenant): TenantData => TenantDataMapper::toData($tenant));

        return TenantData::collect($tenants, DataCollection::class);
    }
}
