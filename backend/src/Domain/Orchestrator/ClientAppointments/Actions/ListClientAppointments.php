<?php

declare(strict_types=1);

namespace Domain\Orchestrator\ClientAppointments\Actions;

use Domain\Client\Models\Client;
use Domain\Orchestrator\Barbershop\Actions\ListBarbershopContacts;
use Domain\Orchestrator\ClientAppointments\Data\ClientAppointmentData;
use Domain\Review\Actions\ListReviewedBookingIds;
use Domain\Review\Data\BookingIdsData;
use Domain\Schedule\Actions\ListClientBookings;
use Domain\Schedule\Data\ClientBookingData;
use Domain\Schedule\Data\ClientBookingsQueryData;
use Domain\Tenant\Data\TenantIdsData;
use Spatie\LaravelData\DataCollection;

/**
 * The signed-in client's appointments at every barbershop where they booked (newest first). No marketplace:
 * only barbershops of their own bookings show up.
 */
final class ListClientAppointments
{
    public function __construct(
        private readonly ListClientBookings $listBookings,
        private readonly ListBarbershopContacts $listContacts,
        private readonly ListReviewedBookingIds $listReviewed,
    ) {}

    /** @return DataCollection<int, ClientAppointmentData> */
    public function handle(): DataCollection
    {
        /** @var Client $client */
        $client = auth('client')->user();
        $bookings = collect($this->listBookings->handle(new ClientBookingsQueryData(client_id: $client->id))->all());

        $barbershops = $this->listContacts->handle(new TenantIdsData(ids: $bookings->pluck('tenant_id')->unique()->values()->all()));
        $reviewed = array_flip($this->listReviewed->handle(new BookingIdsData(
            ids: $bookings->pluck('id')->map(fn (mixed $id): int => (int) $id)->all(),
        ))->ids);

        $appointments = $bookings->map(fn (ClientBookingData $booking): ClientAppointmentData => new ClientAppointmentData(
            id: $booking->id,
            service_name: $booking->service_name,
            date: $booking->date,
            starts_at: $booking->starts_at,
            ends_at: $booking->ends_at,
            status: $booking->status,
            barbershop: $barbershops[$booking->tenant_id],
            has_review: isset($reviewed[$booking->id]),
        ));

        return ClientAppointmentData::collect($appointments, DataCollection::class);
    }
}
