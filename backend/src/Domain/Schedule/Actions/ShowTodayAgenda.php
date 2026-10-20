<?php

declare(strict_types=1);

namespace Domain\Schedule\Actions;

use Domain\Schedule\Data\BookingRequestData;
use Domain\Schedule\Enums\BookingStatus;
use Domain\Schedule\Models\BookingRequest;
use Domain\Tenant\Context\TenantClock;
use Spatie\LaravelData\DataCollection;

/** Today's accepted appointments (confirmed or already concluded), by time. */
final class ShowTodayAgenda
{
    public function __construct(private readonly TenantClock $clock) {}

    /** @return DataCollection<int, BookingRequestData> */
    public function handle(): DataCollection
    {
        $requests = BookingRequest::query()
            ->where('date', $this->clock->today()->toDateString())
            ->whereIn('status', [BookingStatus::Confirmed, BookingStatus::Completed])
            ->chronological()
            ->get();

        return BookingRequestData::collect($requests, DataCollection::class);
    }
}
