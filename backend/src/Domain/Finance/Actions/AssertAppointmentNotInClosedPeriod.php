<?php

declare(strict_types=1);

namespace Domain\Finance\Actions;

use Domain\Finance\Exceptions\PeriodClosedException;
use Domain\Finance\Models\CommissionClosing;
use Domain\Finance\Models\CommissionEntry;

/** Blocks undoing a completed appointment that sits in an active (not reopened) closing. */
final class AssertAppointmentNotInClosedPeriod
{
    public function handle(int $bookingRequestId, string $appointmentDate): void
    {
        $entry = CommissionEntry::query()->where('booking_request_id', $bookingRequestId)->first();
        if ($entry?->closing_id !== null) {
            $closing = CommissionClosing::query()->find($entry->closing_id);
            if ($closing !== null && $closing->reopened_at === null) {
                throw new PeriodClosedException;
            }
        }

        $active = CommissionClosing::query()
            ->whereNull('reopened_at')
            ->where('from', '<=', $appointmentDate)
            ->where('to', '>=', $appointmentDate)
            ->exists();
        if ($active) {
            throw new PeriodClosedException;
        }
    }
}
