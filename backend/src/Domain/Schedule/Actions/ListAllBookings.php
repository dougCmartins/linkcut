<?php

declare(strict_types=1);

namespace Domain\Schedule\Actions;

use Domain\Schedule\Data\BookingRequestData;
use Domain\Schedule\Models\BookingRequest;
use Spatie\LaravelData\DataCollection;

/** Every booking request of the current barbershop, oldest first (data export / portability). */
final class ListAllBookings
{
    /** @return DataCollection<int, BookingRequestData> */
    public function handle(): DataCollection
    {
        return BookingRequestData::collect(BookingRequest::query()->chronological()->get(), DataCollection::class);
    }
}
