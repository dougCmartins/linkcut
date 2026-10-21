<?php

declare(strict_types=1);

namespace Domain\Finance\Actions;

use Domain\Finance\Data\HistoryPageData;
use Domain\Finance\Data\HistoryQueryData;
use Domain\Finance\Data\HistorySummaryData;
use Domain\Finance\Data\RevenueEntryData;
use Domain\Finance\Models\RevenueEntry;
use Spatie\LaravelData\DataCollection;

/** One page of the history (newest first) and the totals of the whole period (two queries). */
final class ListHistory
{
    public function handle(HistoryQueryData $data): HistoryPageData
    {
        $period = fn () => RevenueEntry::query()->betweenDates($data->from, $data->to);

        // Count and total in one aggregate, then only the rows of the page.
        $totals = $period()->selectRaw('count(*) as entries, coalesce(sum(price_in_cents), 0) as total')->toBase()->first();
        $count = (int) $totals->entries;
        $total = (int) $totals->total;
        $items = $period()->newestFirst()->forPage($data->page, $data->per_page)->get();

        return new HistoryPageData(
            items: RevenueEntryData::collect($items, DataCollection::class),
            summary: new HistorySummaryData(
                total_in_cents: $total,
                count: $count,
                average_ticket_in_cents: $count ? (int) round($total / $count) : 0,
            ),
            page: $data->page,
            per_page: $data->per_page,
            last_page: max(1, (int) ceil($count / $data->per_page)),
        );
    }
}
