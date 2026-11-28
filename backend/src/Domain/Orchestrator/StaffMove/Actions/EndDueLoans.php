<?php

declare(strict_types=1);

namespace Domain\Orchestrator\StaffMove\Actions;

use Domain\Orchestrator\StaffMove\Exceptions\StaffHasUpcomingAppointmentsException;
use Domain\Team\Actions\ListDueLoans;
use Domain\Team\Actions\ShowActiveLoan;
use Domain\Team\Data\StaffMoveIdData;
use Domain\Tenant\Context\CurrentTenant;
use Domain\Tenant\Context\TenantClock;
use Illuminate\Support\Facades\Log;

/**
 * Daily: brings back every barber whose loan ended (the day after `ends_on`, in the destination's local time).
 * Idempotent: an ended loan is no longer listed. A loan with bookings still to attend is left as it is and logged.
 */
final class EndDueLoans
{
    public function __construct(
        private readonly ListDueLoans $listDueLoans,
        private readonly ShowActiveLoan $showLoan,
        private readonly ReturnFromLoan $returnFromLoan,
        private readonly CurrentTenant $currentTenant,
        private readonly TenantClock $clock,
    ) {}

    public function handle(): int
    {
        $ended = 0;
        // Candidates by the UTC day after tomorrow (a superset); each one is checked against its own local day.
        foreach ($this->listDueLoans->handle(now()->addDays(2)->toDateString()) as $candidate) {
            $loan = $this->showLoan->handle(new StaffMoveIdData(id: $candidate['id'], organization_id: $candidate['organization_id']));
            $today = $this->currentTenant->run($loan->to_tenant_id, fn (): string => $this->clock->today()->toDateString());
            if ($loan->ends_on >= $today) {
                continue;
            }
            try {
                $this->returnFromLoan->handle($loan, null);
                $ended++;
            } catch (StaffHasUpcomingAppointmentsException) {
                Log::warning('team_loan_not_ended_upcoming_appointments', ['move_id' => $loan->id]);
            }
        }

        return $ended;
    }
}
