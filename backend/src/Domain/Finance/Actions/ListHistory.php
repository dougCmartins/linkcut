<?php

declare(strict_types=1);

namespace Domain\Finance\Actions;

use Domain\Finance\Data\HistoryPageData;
use Domain\Finance\Data\HistoryQueryData;
use Domain\Finance\Data\HistorySummaryData;
use Domain\Finance\Data\RevenueEntryData;
use Domain\Finance\Models\RevenueEntry;
use Spatie\LaravelData\DataCollection;

/** One page of the history (newest first) and the totals of the whole period. */
final class ListHistory
{
    public function handle(HistoryQueryData $data): HistoryPageData
    {
        $period = fn () => RevenueEntry::query()->betweenDates($data->from, $data->to);

        $page = $period()->newestFirst()->paginate(perPage: $data->per_page, page: $data->page);
        $count = $page->total();
        $total = (int) $period()->sum('price_in_cents');

        return new HistoryPageData(
            items: RevenueEntryData::collect($page->items(), DataCollection::class),
            summary: new HistorySummaryData(
                total_in_cents: $total,
                count: $count,
                average_ticket_in_cents: $count ? (int) round($total / $count) : 0,
            ),
            page: $page->currentPage(),
            per_page: $page->perPage(),
            last_page: $page->lastPage(),
        );
    }
}
