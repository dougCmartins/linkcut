<?php

declare(strict_types=1);

namespace Domain\Schedule\Actions;

use Domain\Schedule\Data\StaffBookingsQueryData;
use Domain\Schedule\Data\UpcomingBookingData;
use Domain\Schedule\Enums\BookingStatus;
use Domain\Schedule\Models\BookingRequest;
use Domain\Tenant\Context\TenantClock;

/** The barber's bookings still to happen at the current unit (pending or confirmed, from today on, local time). */
final class ListUpcomingStaffBookings
{
    public function __construct(private readonly TenantClock $clock) {}

    /** @return list<UpcomingBookingData> */
    public function handle(StaffBookingsQueryData $data): array
    {
        return BookingRequest::query()
            ->where('staff_id', $data->staff_id)
            ->whereIn('status', [BookingStatus::Pending, BookingStatus::Confirmed])
            ->where('date', '>=', $this->clock->today()->toDateString())
            ->orderBy('date')->orderBy('starts_at')
            ->get(['id', 'date', 'starts_at', 'client_name'])
            ->map(fn (BookingRequest $booking): UpcomingBookingData => new UpcomingBookingData(
                id: $booking->id,
                date: (string) $booking->getRawOriginal('date'),
                starts_at: substr((string) $booking->getRawOriginal('starts_at'), 0, 5),
                client_name: $booking->client_name,
            ))
            ->values()
            ->all();
    }
}
