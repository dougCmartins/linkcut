<?php

declare(strict_types=1);

namespace Domain\Tenant\Actions;

use Domain\Tenant\Context\CurrentTenant;
use Domain\Tenant\Data\TenantData;
use Domain\Tenant\Models\Tenant;
use Domain\Tenant\Support\TenantDataMapper;

/** The barber finished the first-run wizard: the panel stops sending them there. */
final class CompleteOnboarding
{
    public function __construct(private readonly CurrentTenant $currentTenant) {}

    public function handle(): TenantData
    {
        $tenant = Tenant::query()->findOrFail($this->currentTenant->id());
        if ($tenant->onboarded_at === null) {
            $tenant->update(['onboarded_at' => now()]);
        }

        return TenantDataMapper::toData($tenant);
    }
}
