<?php

declare(strict_types=1);

namespace Domain\Schedule\Actions;

use Domain\Schedule\Data\BookingRequestIdsData;
use Domain\Schedule\Models\BookingRequest;

/** One UPDATE for every reminder sent in a run (they go only once). */
final class MarkBookingsReminded
{
    public function handle(BookingRequestIdsData $data): void
    {
        if ($data->ids === []) {
            return;
        }
        BookingRequest::withoutTenantScope()->whereKey($data->ids)->update(['reminded_at' => now()]);
    }
}
