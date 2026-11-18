<?php

declare(strict_types=1);

namespace Domain\Tenant\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;

/** A brand with branches and its active units, main unit first. */
final class BrandData extends Data
{
    /** @param list<BrandUnitData> $units */
    public function __construct(
        public readonly string $slug,
        public readonly string $name,
        #[DataCollectionOf(BrandUnitData::class)]
        public readonly array $units,
    ) {}
}
