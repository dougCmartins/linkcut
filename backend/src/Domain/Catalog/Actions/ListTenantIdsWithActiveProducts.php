<?php

declare(strict_types=1);

namespace Domain\Catalog\Actions;

use Domain\Catalog\Models\Product;
use Domain\Tenant\Data\TenantIdsData;

/** Which of these barbershops have an active product. One query, no tenant (the client area has none). */
final class ListTenantIdsWithActiveProducts
{
    public function handle(TenantIdsData $data): TenantIdsData
    {
        if (! $data->ids) {
            return new TenantIdsData(ids: []);
        }

        $ids = Product::withoutTenantScope()
            ->where('is_active', true)
            ->whereIn('tenant_id', $data->ids)
            ->distinct()
            ->pluck('tenant_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        return new TenantIdsData(ids: $ids);
    }
}
