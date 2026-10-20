<?php

declare(strict_types=1);

namespace Domain\Finance\Data;

use Spatie\LaravelData\Data;

/** Totals of the whole period (not only of the page). */
final class HistorySummaryData extends Data
{
    public function __construct(
        public readonly int $total_in_cents,
        public readonly int $count,
        public readonly int $average_ticket_in_cents,
    ) {}
}
