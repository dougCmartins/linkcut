<?php

declare(strict_types=1);

namespace Domain\Tenant\Actions;

use Domain\Tenant\Data\NewOrganizationData;
use Domain\Tenant\Data\OrganizationData;
use Domain\Tenant\Exceptions\SlugUnavailableException;
use Domain\Tenant\Models\Organization;
use Illuminate\Database\UniqueConstraintViolationException;

final class CreateOrganization
{
    public function handle(NewOrganizationData $data): OrganizationData
    {
        if (! CheckSlugAvailability::isFree($data->slug)) {
            throw new SlugUnavailableException;
        }
        try {
            $organization = Organization::query()->create([
                'owner_user_id' => $data->owner_user_id,
                'type' => $data->type,
                'name' => $data->name,
                'slug' => $data->slug,
            ]);
        } catch (UniqueConstraintViolationException) {
            throw new SlugUnavailableException;
        }

        return new OrganizationData(
            id: $organization->id,
            owner_user_id: $organization->owner_user_id,
            type: $organization->type,
            name: $organization->name,
            slug: $organization->slug,
        );
    }
}
