<?php

declare(strict_types=1);

namespace Domain\Finance\Actions;

use Domain\Finance\Data\RecordRevenueData;
use Domain\Finance\Data\RevenueEntryData;
use Domain\Finance\Models\RevenueEntry;
use Illuminate\Support\Facades\Log;

/**
 * Puts a concluded appointment in the history. Idempotent by booking request:
 * concluding the same request twice keeps one entry. Called by the Schedule domain (stage 3).
 */
final class RecordServiceRevenue
{
    public function handle(RecordRevenueData $data): RevenueEntryData
    {
        $entry = RevenueEntry::query()->updateOrCreate(
            ['booking_request_id' => $data->booking_request_id],
            [
                'date' => $data->date,
                'time' => $data->time,
                'client_name' => $data->client_name,
                'service_name' => $data->service_name,
                'price_in_cents' => $data->price_in_cents,
                'cost_in_cents' => $data->cost_in_cents,
            ],
        );
        Log::info('finance_revenue_recorded', ['booking_request_id' => $data->booking_request_id, 'entry_id' => $entry->id]);

        return RevenueEntryData::from($entry);
    }
}
