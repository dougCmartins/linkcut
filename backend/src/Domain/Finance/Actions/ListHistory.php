<?php

declare(strict_types=1);

namespace Domain\Finance\Actions;

use Domain\Finance\Data\HistoryPageData;
use Domain\Finance\Data\HistoryQueryData;
use Domain\Finance\Data\HistorySummaryData;
use Domain\Finance\Data\RevenueEntryData;
use Domain\Finance\Models\RevenueEntry;
use Illuminate\Database\Eloquent\Builder;
use Spatie\LaravelData\DataCollection;

/**
 * The history in slices for the infinite scroll, newest first. A cursor (the last row seen) instead of a page number:
 * each slice is one index seek on (tenant_id, date, time), however far the barber scrolls. The totals are computed
 * once, with the first slice.
 */
final class ListHistory
{
    public const PER_PAGE = 50;

    public function handle(HistoryQueryData $data): HistoryPageData
    {
        $period = fn () => RevenueEntry::query()->betweenDates($data->from, $data->to);

        $page = $period()->newestFirst()->cursorPaginate(self::PER_PAGE, ['*'], 'cursor', $data->cursor);

        return new HistoryPageData(
            items: RevenueEntryData::collect($page->items(), DataCollection::class),
            summary: $data->cursor === null ? $this->summary($period()) : null,
            next_cursor: $page->nextCursor()?->encode(),
        );
    }

    /** @param Builder<RevenueEntry> $query */
    private function summary($query): HistorySummaryData
    {
        // Count and total in one aggregate, read from the covering index (tenant_id, date, price_in_cents, …).
        $totals = $query->selectRaw('count(*) as entries, coalesce(sum(price_in_cents), 0) as total')->toBase()->first();
        $count = (int) $totals->entries;
        $total = (int) $totals->total;

        return new HistorySummaryData(
            total_in_cents: $total,
            count: $count,
            average_ticket_in_cents: $count ? (int) round($total / $count) : 0,
        );
    }
}
