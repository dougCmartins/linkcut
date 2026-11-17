<?php

declare(strict_types=1);

namespace Domain\Tenant\Data;

use Spatie\LaravelData\Data;

/** A user and the organization they claim to own. */
final class OrganizationOwnerData extends Data
{
    public function __construct(
        public readonly int $organization_id,
        public readonly int $user_id,
    ) {}
}
