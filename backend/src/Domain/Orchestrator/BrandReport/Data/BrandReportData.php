<?php

declare(strict_types=1);

namespace Domain\Orchestrator\BrandReport\Data;

use Spatie\LaravelData\Data;

/** The brand over a period: the total and the breakdown by unit. */
final class BrandReportData extends Data
{
    /**
     * @param  list<BrandUnitReportData>  $units
     */
    public function __construct(
        public readonly string $from,
        public readonly string $to,
        public readonly BrandTotalsData $total,
        public readonly array $units,
    ) {}
}
