<?php

declare(strict_types=1);

namespace Domain\Schedule\Actions;

use Domain\Schedule\Data\BookingRequestData;
use Domain\Schedule\Data\ReminderWindowData;
use Domain\Schedule\Enums\BookingStatus;
use Domain\Schedule\Models\BookingRequest;
use Spatie\LaravelData\DataCollection;

/** Confirmed appointments of the current barbershop starting in the window, not reminded yet, with an e-mail. */
final class ListRemindableBookings
{
    /** @return DataCollection<int, BookingRequestData> */
    public function handle(ReminderWindowData $window): DataCollection
    {
        [$fromDate] = explode(' ', $window->from);
        [$toDate] = explode(' ', $window->to);
        $bookings = BookingRequest::query()
            ->where('status', BookingStatus::Confirmed)
            ->whereNull('reminded_at')
            ->where('client_email', '!=', '')
            ->whereBetween('date', [$fromDate, $toDate])
            ->chronological()
            ->get()
            ->filter(function (BookingRequest $booking) use ($window): bool {
                $startsAt = "{$booking->date} {$booking->starts_at}";

                return $startsAt >= $window->from && $startsAt <= $window->to;
            });

        return BookingRequestData::collect($bookings->values(), DataCollection::class);
    }
}
