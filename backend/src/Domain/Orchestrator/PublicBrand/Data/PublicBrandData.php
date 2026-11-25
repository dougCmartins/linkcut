<?php

declare(strict_types=1);

namespace Domain\Orchestrator\PublicBrand\Data;

use Spatie\LaravelData\Data;

/** Public brand (organization) with its active units for the unit select. */
final class PublicBrandData extends Data
{
    /**
     * @param  list<BrandUnitData>  $units
     */
    public function __construct(
        public readonly string $name,
        public readonly string $slug,
        public readonly array $units,
    ) {}
}
