<?php

declare(strict_types=1);

namespace Domain\Tenant\Data;

use Domain\Tenant\Enums\OrganizationType;
use Spatie\LaravelData\Data;

final class OrganizationData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly ?int $owner_user_id,
        public readonly OrganizationType $type,
        public readonly string $name,
        public readonly string $slug,
    ) {}
}
