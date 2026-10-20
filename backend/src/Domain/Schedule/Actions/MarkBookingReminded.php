<?php

declare(strict_types=1);

namespace Domain\Schedule\Actions;

use Domain\Schedule\Data\BookingRequestIdData;
use Domain\Schedule\Models\BookingRequest;

/** The day-before reminder went out: never send it twice. */
final class MarkBookingReminded
{
    public function handle(BookingRequestIdData $id): void
    {
        BookingRequest::query()->whereKey($id->id)->update(['reminded_at' => now()]);
    }
}
