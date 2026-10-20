<?php

declare(strict_types=1);

namespace Domain\Schedule\Actions;

use Domain\Schedule\Data\ClientBookingData;
use Domain\Schedule\Data\ClientBookingsQueryData;
use Domain\Schedule\Models\BookingRequest;
use Spatie\LaravelData\DataCollection;

/**
 * Every booking of one client, in any barbershop (the client area is global). The only Schedule query
 * outside the tenant scope, always filtered by the signed-in client.
 */
final class ListClientBookings
{
    /** @return DataCollection<int, ClientBookingData> */
    public function handle(ClientBookingsQueryData $client): DataCollection
    {
        $bookings = BookingRequest::withoutTenantScope()
            ->where('client_id', $client->client_id)
            ->orderByDesc('date')
            ->orderByDesc('starts_at')
            ->get()
            ->map(fn (BookingRequest $booking): ClientBookingData => new ClientBookingData(
                id: $booking->id,
                tenant_id: $booking->tenant_id,
                service_name: $booking->service_name,
                date: $booking->date,
                starts_at: $booking->starts_at,
                ends_at: $booking->ends_at,
                status: $booking->status,
            ));

        return ClientBookingData::collect($bookings, DataCollection::class);
    }
}
