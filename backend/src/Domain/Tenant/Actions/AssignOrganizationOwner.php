<?php

declare(strict_types=1);

namespace Domain\Tenant\Actions;

use Domain\Tenant\Data\OrganizationOwnerData;
use Domain\Tenant\Models\Organization;

/** At sign-up the barbershop exists before its owner: this links them once the owner is created. */
final class AssignOrganizationOwner
{
    public function handle(OrganizationOwnerData $data): void
    {
        Organization::query()->whereKey($data->organization_id)->update(['owner_user_id' => $data->user_id]);
    }
}
