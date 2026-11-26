<?php

declare(strict_types=1);

namespace Domain\Tenant\Data;

use Spatie\LaravelData\Data;

/** A unit that must belong to a given organization (brand). */
final class OrganizationUnitData extends Data
{
    public function __construct(
        public readonly int $organization_id,
        public readonly int $unit_id,
    ) {}
}
