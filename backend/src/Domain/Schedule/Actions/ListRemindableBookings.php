<?php

declare(strict_types=1);

namespace Domain\Schedule\Actions;

use Domain\Schedule\Data\BookingRequestData;
use Domain\Schedule\Data\RemindableBookingData;
use Domain\Schedule\Data\ReminderWindowData;
use Domain\Schedule\Enums\BookingStatus;
use Domain\Schedule\Models\BookingRequest;
use Spatie\LaravelData\DataCollection;

/**
 * Confirmed appointments starting in the window, not reminded yet, with an e-mail. One query for every
 * barbershop of the same timezone (the scheduler groups them), not one per barbershop.
 */
final class ListRemindableBookings
{
    /** @return DataCollection<int, RemindableBookingData> */
    public function handle(ReminderWindowData $window): DataCollection
    {
        if ($window->tenant_ids === []) {
            return RemindableBookingData::collect([], DataCollection::class);
        }
        [$fromDate] = explode(' ', $window->from);
        [$toDate] = explode(' ', $window->to);
        $bookings = BookingRequest::withoutTenantScope()
            ->whereIn('tenant_id', $window->tenant_ids)
            ->where('status', BookingStatus::Confirmed)
            ->whereNull('reminded_at')
            ->whereBetween('date', [$fromDate, $toDate])
            ->where('client_email', '!=', '')
            ->chronological()
            ->get()
            ->filter(function (BookingRequest $booking) use ($window): bool {
                $startsAt = "{$booking->date} {$booking->starts_at}";

                return $startsAt >= $window->from && $startsAt <= $window->to;
            })
            ->map(fn (BookingRequest $booking): RemindableBookingData => new RemindableBookingData(
                tenant_id: $booking->tenant_id,
                booking: BookingRequestData::from($booking),
            ));

        return RemindableBookingData::collect($bookings->values(), DataCollection::class);
    }
}
