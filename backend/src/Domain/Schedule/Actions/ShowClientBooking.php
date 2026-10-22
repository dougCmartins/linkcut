<?php

declare(strict_types=1);

namespace Domain\Schedule\Actions;

use Domain\Schedule\Data\ClientBookingData;
use Domain\Schedule\Data\ClientBookingIdData;
use Domain\Schedule\Exceptions\BookingRequestNotFoundException;
use Domain\Schedule\Models\BookingRequest;

/** One booking of the signed-in client, in any barbershop (the client area has no current tenant). */
final class ShowClientBooking
{
    public function handle(ClientBookingIdData $data): ClientBookingData
    {
        $booking = BookingRequest::withoutTenantScope()
            ->whereKey($data->booking_id)
            ->where('client_id', $data->client_id)
            ->first(['id', 'tenant_id', 'service_name', 'date', 'starts_at', 'ends_at', 'status'])
            ?? throw new BookingRequestNotFoundException;

        return new ClientBookingData(
            id: $booking->id,
            tenant_id: $booking->tenant_id,
            service_name: $booking->service_name,
            date: $booking->date,
            starts_at: $booking->starts_at,
            ends_at: $booking->ends_at,
            status: $booking->status,
        );
    }
}
