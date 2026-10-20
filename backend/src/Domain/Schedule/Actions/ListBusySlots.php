<?php

declare(strict_types=1);

namespace Domain\Schedule\Actions;

use Domain\Schedule\Data\BusySlotData;
use Domain\Schedule\Data\DateRangeData;
use Domain\Schedule\Models\BookingRequest;
use Spatie\LaravelData\DataCollection;

/** Taken times in a range of dates, without any client data (they feed the public free times). */
final class ListBusySlots
{
    /** @return DataCollection<int, BusySlotData> */
    public function handle(DateRangeData $range): DataCollection
    {
        $slots = BookingRequest::query()
            ->blocking()
            ->whereBetween('date', [$range->from, $range->to])
            ->get(['date', 'starts_at', 'ends_at'])
            ->map(fn (BookingRequest $request): BusySlotData => new BusySlotData(
                date: $request->date,
                starts_at: $request->starts_at,
                ends_at: $request->ends_at,
            ));

        return BusySlotData::collect($slots, DataCollection::class);
    }
}
