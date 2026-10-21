<?php

declare(strict_types=1);

namespace Domain\Tenant\Actions;

use Domain\Tenant\Data\TenantData;
use Domain\Tenant\Data\TenantIdsData;
use Domain\Tenant\Models\Tenant;
use Domain\Tenant\Support\TenantDataMapper;
use Spatie\LaravelData\DataCollection;

/** Several barbershops in one query (client area, e-mails). */
final class ListTenantsByIds
{
    /** @return DataCollection<int, TenantData> */
    public function handle(TenantIdsData $data): DataCollection
    {
        $tenants = Tenant::query()
            ->select(Tenant::DATA_COLUMNS)
            ->whereKey($data->ids)
            ->get()
            ->map(fn (Tenant $tenant): TenantData => TenantDataMapper::toData($tenant));

        return TenantData::collect($tenants, DataCollection::class);
    }
}
