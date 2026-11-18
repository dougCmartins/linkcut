<?php

declare(strict_types=1);

namespace Domain\Orchestrator\PublicPage\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;

/** GET /api/public/brand/{slug}: the brand and its active units, main unit first. */
final class PublicBrandData extends Data
{
    /** @param list<PublicBrandUnitData> $units */
    public function __construct(
        public readonly string $slug,
        public readonly string $name,
        #[DataCollectionOf(PublicBrandUnitData::class)]
        public readonly array $units,
    ) {}
}
