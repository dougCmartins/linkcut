<?php

declare(strict_types=1);

namespace Domain\Finance\Actions;

use Domain\Finance\Data\BookingRequestIdData;
use Domain\Finance\Models\CommissionEntry;
use Illuminate\Support\Facades\Log;

final class RemoveCommissionEntry
{
    public function handle(BookingRequestIdData $data): void
    {
        $deleted = CommissionEntry::query()->where('booking_request_id', $data->booking_request_id)->delete();
        if ($deleted > 0) {
            Log::info('finance_commission_removed', ['booking_request_id' => $data->booking_request_id]);
        }
    }
}
