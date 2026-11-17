<?php

declare(strict_types=1);

namespace Domain\Tenant\Data;

use Domain\Tenant\Enums\OrganizationType;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;

/** The units this user can open, main unit first. `can_manage`: the owner, who may create branches. */
final class UnitListData extends Data
{
    /** @param list<UnitData> $units */
    public function __construct(
        public readonly int $organization_id,
        public readonly string $organization_name,
        public readonly OrganizationType $organization_type,
        public readonly bool $can_manage,
        #[DataCollectionOf(UnitData::class)]
        public readonly array $units,
    ) {}
}
