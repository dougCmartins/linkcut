<?php

declare(strict_types=1);

namespace Domain\Finance\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

/**
 * Some rows of the history, newest first. `summary` (totals of the whole period) comes only with the first rows;
 * `next_cursor` asks for the next ones and is null at the end.
 */
final class HistoryPageData extends Data
{
    /** @param DataCollection<int, RevenueEntryData> $items */
    public function __construct(
        #[DataCollectionOf(RevenueEntryData::class)]
        public readonly DataCollection $items,
        public readonly ?HistorySummaryData $summary,
        public readonly ?string $next_cursor,
    ) {}
}
