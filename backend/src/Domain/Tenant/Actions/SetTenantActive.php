<?php

declare(strict_types=1);

namespace Domain\Tenant\Actions;

use Domain\Tenant\Data\TenantActivationData;
use Domain\Tenant\Data\TenantData;
use Domain\Tenant\Exceptions\TenantNotFoundException;
use Domain\Tenant\Models\Tenant;
use Domain\Tenant\Support\TenantDataMapper;
use Illuminate\Support\Facades\Log;

/**
 * Turns a barbershop off or on (platform operator: artisan tenant:deactivate/activate). An inactive one has its
 * public page answering 404 and its barbers refused at sign-in (EnsureTenantIsActive, FindActiveTenantBySlug).
 */
final class SetTenantActive
{
    public function handle(TenantActivationData $data): TenantData
    {
        $tenant = Tenant::query()->where('slug', $data->slug)->first() ?? throw new TenantNotFoundException;
        $tenant->update(['active' => $data->active]);
        Log::channel('security')->warning('tenant_activation_changed', ['tenant_id' => $tenant->id, 'active' => $data->active]);

        return TenantDataMapper::toData($tenant);
    }
}
