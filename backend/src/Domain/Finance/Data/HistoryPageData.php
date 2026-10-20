<?php

declare(strict_types=1);

namespace Domain\Finance\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

/** One page of the history plus the totals of the period. */
final class HistoryPageData extends Data
{
    /** @param DataCollection<int, RevenueEntryData> $items */
    public function __construct(
        #[DataCollectionOf(RevenueEntryData::class)]
        public readonly DataCollection $items,
        public readonly HistorySummaryData $summary,
        public readonly int $page,
        public readonly int $per_page,
        public readonly int $last_page,
    ) {}
}
