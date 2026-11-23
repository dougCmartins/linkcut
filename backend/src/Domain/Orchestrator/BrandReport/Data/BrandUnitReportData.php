<?php

declare(strict_types=1);

namespace Domain\Orchestrator\BrandReport\Data;

use Spatie\LaravelData\Data;

final class BrandUnitReportData extends Data
{
    public function __construct(
        public readonly int $unit_id,
        public readonly string $name,
        public readonly string $slug,
        public readonly bool $is_main,
        public readonly BrandTotalsData $totals,
    ) {}
}
