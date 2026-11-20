<?php

declare(strict_types=1);

namespace Domain\Orchestrator\Organization\Data;

use Domain\Tenant\Enums\OrganizationType;
use Spatie\LaravelData\Data;

final class OrganizationSummaryData extends Data
{
    /**
     * @param  list<UnitData>  $units
     */
    public function __construct(
        public readonly int $id,
        public readonly OrganizationType $type,
        public readonly string $name,
        public readonly string $slug,
        public readonly array $units,
    ) {}
}
