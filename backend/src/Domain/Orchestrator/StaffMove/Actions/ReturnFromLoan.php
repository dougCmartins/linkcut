<?php

declare(strict_types=1);

namespace Domain\Orchestrator\StaffMove\Actions;

use Domain\Identity\Actions\MoveUserToUnit;
use Domain\Identity\Data\UserUnitData;
use Domain\Identity\Models\User;
use Domain\Orchestrator\StaffMove\Exceptions\StaffHasUpcomingAppointmentsException;
use Domain\Orchestrator\StaffMove\Notifications\StaffMovedNotification;
use Domain\Schedule\Actions\ListUpcomingStaffBookings;
use Domain\Schedule\Data\StaffBookingsQueryData;
use Domain\Team\Actions\CloseLoan;
use Domain\Team\Data\ActiveLoanData;
use Domain\Team\Data\StaffMoveData;
use Domain\Tenant\Actions\EnsureTenantIsActive;
use Domain\Tenant\Context\CurrentTenant;
use Domain\Tenant\Context\TenantClock;
use Domain\Tenant\Data\TenantIdData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * The barber comes back from a loan ("Encerrar agora" or the automatic return), across domains: refused while they
 * still have bookings to attend at the destination (the owner passes them first); otherwise their row at the origin
 * is on again and the one at the destination off (Team), their login opens the origin again (Identity), e-mail.
 */
final class ReturnFromLoan
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
        private readonly TenantClock $clock,
        private readonly EnsureTenantIsActive $ensureTenantIsActive,
        private readonly ListUpcomingStaffBookings $listUpcoming,
        private readonly CloseLoan $closeLoan,
        private readonly MoveUserToUnit $moveUser,
    ) {}

    public function handle(ActiveLoanData $loan, ?int $byUserId): StaffMoveData
    {
        $upcoming = $this->currentTenant->run($loan->to_tenant_id, fn (): array => $this->listUpcoming->handle(new StaffBookingsQueryData(staff_id: $loan->to_staff_id)));
        if ($upcoming !== []) {
            throw new StaffHasUpcomingAppointmentsException($upcoming);
        }
        $origin = $this->ensureTenantIsActive->handle(new TenantIdData(tenant_id: $loan->from_tenant_id));

        $move = DB::transaction(function () use ($loan, $byUserId): StaffMoveData {
            $move = $this->closeLoan->handle($loan, $byUserId);
            $this->moveUser->handle(new UserUnitData(user_id: $loan->user_id, tenant_id: $loan->from_tenant_id));

            return $move;
        });

        $today = $this->currentTenant->run($origin->id, fn (): string => $this->clock->today()->toDateString());
        User::query()->find($loan->user_id)?->notify(new StaffMovedNotification($origin->name, $today));
        Log::info('team_loan_ended', ['move_id' => $loan->id, 'by_user_id' => $byUserId]);

        return $move;
    }
}
