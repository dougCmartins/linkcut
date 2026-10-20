<?php

declare(strict_types=1);

namespace Domain\Finance\Actions;

use Domain\Finance\Data\BookingRequestIdData;
use Domain\Finance\Models\RevenueEntry;
use Illuminate\Support\Facades\Log;

/** Takes a concluded appointment out of the history (undo of "Concluir"). Nothing happens when there is none. */
final class RemoveServiceRevenue
{
    public function handle(BookingRequestIdData $data): void
    {
        $removed = RevenueEntry::query()->where('booking_request_id', $data->booking_request_id)->delete();
        Log::info('finance_revenue_removed', ['booking_request_id' => $data->booking_request_id, 'removed' => $removed]);
    }
}
