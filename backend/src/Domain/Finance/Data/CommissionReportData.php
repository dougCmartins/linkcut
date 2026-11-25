<?php

declare(strict_types=1);

namespace Domain\Finance\Data;

use Spatie\LaravelData\Data;

final class CommissionReportData extends Data
{
    /**
     * @param  list<CommissionEntryData>  $entries
     */
    public function __construct(
        public readonly string $from,
        public readonly string $to,
        public readonly int $total_amount_in_cents,
        public readonly array $entries,
    ) {}
}
