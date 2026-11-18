<?php

declare(strict_types=1);

namespace Domain\Tenant\Actions;

use Domain\Tenant\Context\CurrentTenant;
use Domain\Tenant\Enums\OrganizationType;
use Domain\Tenant\Models\Organization;
use Domain\Tenant\Models\Tenant;

/**
 * The brand slug when the current tenant is the main unit of a brand with branches (its page is the brand link,
 * with the branch picker); null otherwise: a barber on their own, or a branch on its own link.
 */
final class ShowBrandSlugOfCurrentTenant
{
    public function __construct(private readonly CurrentTenant $currentTenant) {}

    public function handle(): ?string
    {
        $tenant = Tenant::query()->whereKey($this->currentTenant->id())->where('is_main', true)->first(['id', 'organization_id']);
        if (! $tenant?->organization_id) {
            return null;
        }

        return Organization::query()
            ->whereKey($tenant->organization_id)
            ->where('type', OrganizationType::MultiUnit)
            ->value('slug');
    }
}
