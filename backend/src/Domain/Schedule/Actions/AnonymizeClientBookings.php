<?php

declare(strict_types=1);

namespace Domain\Schedule\Actions;

use Domain\Schedule\Data\ClientBookingsQueryData;
use Domain\Schedule\Models\BookingRequest;

/**
 * A client deleted the account (LGPD): their bookings stay in each barbershop's agenda and history, without the
 * name, WhatsApp and e-mail. Every barbershop at once, so without the tenant scope on purpose.
 * Returns the ids of the anonymized bookings (Finance anonymizes their revenue entries).
 */
final class AnonymizeClientBookings
{
    /** @return list<int> */
    public function handle(ClientBookingsQueryData $data): array
    {
        $bookings = BookingRequest::withoutTenantScope()->where(function ($query) use ($data): void {
            $query->where('client_id', $data->client_id);
            if ($data->email !== null && $data->email !== '') {
                $query->orWhere(fn ($old) => $old->whereNull('client_id')->where('client_email', mb_strtolower($data->email)));
            }
        });
        $ids = (clone $bookings)->pluck('id')->all();
        $bookings->update([
            'client_name' => __('client.deleted_name'),
            'client_whatsapp' => '',
            'client_email' => '',
            'client_distance_meters' => null,
            'client_id' => null,
        ]);

        return $ids;
    }
}
