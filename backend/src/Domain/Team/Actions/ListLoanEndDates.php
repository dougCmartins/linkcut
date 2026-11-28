<?php

declare(strict_types=1);

namespace Domain\Team\Actions;

use Domain\Team\Enums\StaffMoveKind;
use Domain\Team\Enums\StaffMoveStatus;
use Domain\Team\Models\StaffMove;

/**
 * The last day each borrowed barber works here (their active loan's `ends_on`), for the public agenda: after it they
 * are not offered. One query for any number of barbers; a barber who is not on loan is simply absent.
 */
final class ListLoanEndDates
{
    /**
     * @param  list<int>  $staffIds
     * @return array<int, string> staff id => last day (Y-m-d)
     */
    public function handle(array $staffIds): array
    {
        if ($staffIds === []) {
            return [];
        }

        return StaffMove::query()
            ->whereIn('to_staff_id', $staffIds)
            ->where('kind', StaffMoveKind::Loan)
            ->where('status', StaffMoveStatus::Active)
            ->get(['to_staff_id', 'ends_on'])
            ->mapWithKeys(fn (StaffMove $move): array => [(int) $move->to_staff_id => $move->ends_on?->toDateString()])
            ->filter()
            ->all();
    }
}
