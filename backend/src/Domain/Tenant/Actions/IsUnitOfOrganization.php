<?php

declare(strict_types=1);

namespace Domain\Tenant\Actions;

use Domain\Tenant\Data\OrganizationUnitData;
use Domain\Tenant\Models\Tenant;

/** Whether the unit is one of the brand's (moves and brand-wide actions never leave the brand). */
final class IsUnitOfOrganization
{
    public function handle(OrganizationUnitData $data): bool
    {
        return Tenant::query()->whereKey($data->unit_id)->where('organization_id', $data->organization_id)->exists();
    }
}
