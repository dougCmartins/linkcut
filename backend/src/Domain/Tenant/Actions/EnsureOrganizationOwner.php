<?php

declare(strict_types=1);

namespace Domain\Tenant\Actions;

use Domain\Tenant\Data\OrganizationOwnerData;
use Domain\Tenant\Exceptions\OrganizationOwnerOnlyException;
use Domain\Tenant\Models\Organization;

/** Refuses anybody but the owner of the organization (creating branches, brand-wide actions). */
final class EnsureOrganizationOwner
{
    public function handle(OrganizationOwnerData $data): void
    {
        $isOwner = Organization::query()->whereKey($data->organization_id)->where('owner_user_id', $data->user_id)->exists();
        if (! $isOwner) {
            throw new OrganizationOwnerOnlyException;
        }
    }
}
