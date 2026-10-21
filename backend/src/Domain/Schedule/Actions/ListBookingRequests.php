<?php

declare(strict_types=1);

namespace Domain\Schedule\Actions;

use Domain\Schedule\Data\BookingRequestData;
use Domain\Schedule\Data\BookingRequestQueryData;
use Domain\Schedule\Models\BookingRequest;
use Domain\Tenant\Context\TenantClock;
use Spatie\LaravelData\DataCollection;

/** Requests with the given status from today on (older ones no longer need an answer). */
final class ListBookingRequests
{
    /** Safety cap: the dashboard never loads an unbounded list. */
    private const LIMIT = 200;

    public function __construct(private readonly TenantClock $clock) {}

    /** @return DataCollection<int, BookingRequestData> */
    public function handle(BookingRequestQueryData $data): DataCollection
    {
        $requests = BookingRequest::query()
            ->where('status', $data->status)
            ->where('date', '>=', $this->clock->today()->toDateString())
            ->chronological()
            ->limit(self::LIMIT)
            ->get();

        return BookingRequestData::collect($requests, DataCollection::class);
    }
}
