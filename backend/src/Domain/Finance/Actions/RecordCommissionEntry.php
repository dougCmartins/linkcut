<?php

declare(strict_types=1);

namespace Domain\Finance\Actions;

use Domain\Finance\Data\CommissionEntryData;
use Domain\Finance\Data\RecordCommissionData;
use Domain\Finance\Models\CommissionEntry;
use Illuminate\Support\Facades\Log;

/** Idempotent snapshot on appointment completion. */
final class RecordCommissionEntry
{
    public function __construct(private readonly CalculateCommission $calculate) {}

    public function handle(RecordCommissionData $data): CommissionEntryData
    {
        $calc = $this->calculate->handle(
            $data->price_in_cents,
            $data->cost_in_cents,
            $data->staff_id,
            $data->date,
            $data->service_lines,
        );

        $adjustments = array_map(
            fn ($line): array => $line->toArray(),
            $calc->adjustments,
        );

        $entry = CommissionEntry::query()->updateOrCreate(
            ['booking_request_id' => $data->booking_request_id],
            [
                'staff_id' => $data->staff_id,
                'date' => $data->date,
                'base_in_cents' => $calc->base_in_cents,
                'percent' => $calc->percent,
                'adjustments_json' => $adjustments,
                'amount_in_cents' => $calc->amount_in_cents,
            ],
        );
        Log::info('finance_commission_recorded', [
            'booking_request_id' => $data->booking_request_id,
            'entry_id' => $entry->id,
            'amount_in_cents' => $entry->amount_in_cents,
        ]);

        return new CommissionEntryData(
            id: $entry->id,
            booking_request_id: (int) $entry->booking_request_id,
            staff_id: (int) $entry->staff_id,
            date: (string) $entry->date,
            base_in_cents: (int) $entry->base_in_cents,
            percent: (int) $entry->percent,
            adjustments: $entry->adjustments_json ?? [],
            amount_in_cents: (int) $entry->amount_in_cents,
            closing_id: $entry->closing_id !== null ? (int) $entry->closing_id : null,
        );
    }
}
