<?php

declare(strict_types=1);

namespace Domain\Finance\Actions;

use Domain\Finance\Data\CommissionEntryData;
use Domain\Finance\Data\CommissionReportData;
use Domain\Finance\Data\CommissionReportQueryData;
use Domain\Finance\Models\CommissionEntry;

/**
 * Raw report by period (optional staff filter). Authorization lives in the Orchestrator presenter.
 */
final class ListCommissionReport
{
    public function handle(CommissionReportQueryData $query): CommissionReportData
    {
        $q = CommissionEntry::query()->betweenDates($query->from, $query->to)->orderBy('date')->orderBy('id');
        if ($query->staff_id !== null) {
            $q->where('staff_id', $query->staff_id);
        }

        $entries = $q->get()->map(fn (CommissionEntry $row): CommissionEntryData => new CommissionEntryData(
            id: $row->id,
            booking_request_id: (int) $row->booking_request_id,
            staff_id: (int) $row->staff_id,
            date: (string) $row->date,
            base_in_cents: (int) $row->base_in_cents,
            percent: (int) $row->percent,
            adjustments: $row->adjustments_json ?? [],
            amount_in_cents: (int) $row->amount_in_cents,
            closing_id: $row->closing_id !== null ? (int) $row->closing_id : null,
        ))->all();

        $total = array_sum(array_map(fn (CommissionEntryData $e): int => $e->amount_in_cents, $entries));

        return new CommissionReportData(
            from: $query->from,
            to: $query->to,
            total_amount_in_cents: $total,
            entries: $entries,
        );
    }
}
