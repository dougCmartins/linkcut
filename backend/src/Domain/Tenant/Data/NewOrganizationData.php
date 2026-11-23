<?php

declare(strict_types=1);

namespace Domain\Tenant\Data;

use Domain\Tenant\Enums\OrganizationType;
use Spatie\LaravelData\Data;

final class NewOrganizationData extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly string $slug,
        public readonly OrganizationType $type = OrganizationType::Individual,
        public readonly ?int $owner_user_id = null,
    ) {}
}
