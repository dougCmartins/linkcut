<?php

declare(strict_types=1);

namespace Domain\Tenant\Actions;

use Domain\Tenant\Data\TenantIdData;
use Domain\Tenant\Models\Tenant;

/** Deletes the barbershop row: settings, images, bookings, finance and push subscriptions go by cascade. */
final class DeleteTenant
{
    public function handle(TenantIdData $data): void
    {
        Tenant::query()->whereKey($data->tenant_id)->delete();
    }
}
