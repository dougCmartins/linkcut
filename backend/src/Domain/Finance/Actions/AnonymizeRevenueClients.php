<?php

declare(strict_types=1);

namespace Domain\Finance\Actions;

use Domain\Finance\Data\BookingRequestIdsData;
use Domain\Finance\Models\RevenueEntry;

/** The history keeps the amounts of a client who deleted the account (LGPD), without their name. */
final class AnonymizeRevenueClients
{
    public function handle(BookingRequestIdsData $data): void
    {
        if ($data->ids === []) {
            return;
        }
        RevenueEntry::withoutTenantScope()->whereIn('booking_request_id', $data->ids)->update(['client_name' => __('client.deleted_name')]);
    }
}
