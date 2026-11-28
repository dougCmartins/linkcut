<?php

declare(strict_types=1);

namespace Domain\Orchestrator\StaffMove\Actions;

use Domain\Identity\Actions\MoveUserToUnit;
use Domain\Identity\Data\UserUnitData;
use Domain\Identity\Enums\UserRole;
use Domain\Identity\Models\User;
use Domain\Orchestrator\StaffMove\Data\MoveStaffData;
use Domain\Orchestrator\StaffMove\Exceptions\MoveDestinationInvalidException;
use Domain\Orchestrator\StaffMove\Exceptions\StaffHasUpcomingAppointmentsException;
use Domain\Orchestrator\StaffMove\Notifications\StaffMovedNotification;
use Domain\Schedule\Actions\ListUpcomingStaffBookings;
use Domain\Schedule\Data\StaffBookingsQueryData;
use Domain\Settings\Actions\ShowScheduleSettings;
use Domain\Team\Actions\DeactivateStaffForMove;
use Domain\Team\Actions\PlaceStaffInUnit;
use Domain\Team\Actions\RecordStaffMove;
use Domain\Team\Actions\ShowMovableStaff;
use Domain\Team\Actions\UpdateStaffBusinessHours;
use Domain\Team\Actions\UpdateStaffServices;
use Domain\Team\Data\PlaceStaffData;
use Domain\Team\Data\StaffIdData;
use Domain\Team\Data\StaffMoveData;
use Domain\Team\Data\StaffMoveRecordData;
use Domain\Team\Data\UpdateStaffHoursData;
use Domain\Team\Data\UpdateStaffServicesData;
use Domain\Team\Enums\StaffMoveKind;
use Domain\Team\Exceptions\LoanEndInvalidException;
use Domain\Team\Exceptions\OwnerRequiredException;
use Domain\Team\Exceptions\StaffServiceUnavailableException;
use Domain\Tenant\Actions\EnsureTenantIsActive;
use Domain\Tenant\Actions\IsUnitOfOrganization;
use Domain\Tenant\Context\CurrentTenant;
use Domain\Tenant\Context\TenantClock;
use Domain\Tenant\Data\OrganizationUnitData;
use Domain\Tenant\Data\TenantIdData;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * "Mover para outra filial", a transfer or a loan until a date (docs/decisoes/0003 P1 and P2), across domains. The current unit is the origin.
 * Refused, with nothing changed, when the barber still has bookings to attend here (the owner passes them first).
 * In one transaction: the barber's row at the destination is turned on (the old one when they worked there before)
 * with the hours, services and switches the owner chose (Team; services checked against the destination's, Settings),
 * their row here is turned off (never deleted: past earnings stay here, with this unit's rules), their login opens
 * the destination from now on (Identity) and the move is written down (Team). Then the barber gets an e-mail.
 */
final class MoveStaff
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
        private readonly TenantClock $clock,
        private readonly EnsureTenantIsActive $ensureTenantIsActive,
        private readonly IsUnitOfOrganization $isUnitOfOrganization,
        private readonly ShowMovableStaff $showMovableStaff,
        private readonly ListUpcomingStaffBookings $listUpcoming,
        private readonly ShowScheduleSettings $showSchedule,
        private readonly PlaceStaffInUnit $placeStaff,
        private readonly UpdateStaffBusinessHours $updateHours,
        private readonly UpdateStaffServices $updateServices,
        private readonly DeactivateStaffForMove $deactivateStaff,
        private readonly MoveUserToUnit $moveUser,
        private readonly RecordStaffMove $recordMove,
    ) {}

    public function handle(StaffIdData $staffId, MoveStaffData $data): StaffMoveData
    {
        $owner = $this->owner();
        $originId = $this->currentTenant->id();
        $organizationId = (int) $owner->organization_id;
        $isSameBrand = $this->isUnitOfOrganization->handle(new OrganizationUnitData(organization_id: $organizationId, unit_id: $data->to_unit_id));
        if ($data->to_unit_id === $originId || ! $isSameBrand) {
            throw new MoveDestinationInvalidException;
        }
        $destination = $this->ensureTenantIsActive->handle(new TenantIdData(tenant_id: $data->to_unit_id));

        $barber = $this->showMovableStaff->handle($staffId);
        $upcoming = $this->listUpcoming->handle(new StaffBookingsQueryData(staff_id: $barber->staff_id));
        if ($upcoming !== []) {
            throw new StaffHasUpcomingAppointmentsException($upcoming);
        }
        $startsOn = $this->clock->today()->toDateString();
        $kind = StaffMoveKind::from($data->kind);
        if ($kind === StaffMoveKind::Loan && ($data->ends_on === null || $data->ends_on <= $startsOn)) {
            throw new LoanEndInvalidException;
        }

        $move = DB::transaction(function () use ($barber, $data, $destination, $originId, $owner, $organizationId, $startsOn, $kind): StaffMoveData {
            $toStaffId = $this->currentTenant->run($destination->id, function () use ($barber, $data): int {
                $this->ensureDestinationServices($data->service_ids);
                $toStaffId = $this->placeStaff->handle(new PlaceStaffData(
                    user_id: $barber->user_id,
                    name: $barber->name,
                    avatar: $barber->avatar,
                    from_staff_id: $barber->staff_id,
                    keep_permissions: $data->permissions === 'keep',
                ));
                $to = new StaffIdData(id: $toStaffId);
                $this->updateHours->handle($to, new UpdateStaffHoursData(weekly_hours: $data->weekly_hours));
                $this->updateServices->handle($to, new UpdateStaffServicesData(service_ids: $data->service_ids));

                return $toStaffId;
            });
            $this->currentTenant->run($originId, fn () => $this->deactivateStaff->handle(new StaffIdData(id: $barber->staff_id)));
            $this->moveUser->handle(new UserUnitData(user_id: $barber->user_id, tenant_id: $destination->id));

            return $this->recordMove->handle(new StaffMoveRecordData(
                organization_id: $organizationId,
                user_id: $barber->user_id,
                from_staff_id: $barber->staff_id,
                to_staff_id: $toStaffId,
                kind: $kind,
                starts_on: $startsOn,
                ends_on: $kind === StaffMoveKind::Loan ? $data->ends_on : null,
                created_by: $owner->id,
            ));
        });

        User::query()->find($barber->user_id)?->notify(new StaffMovedNotification($destination->name, $startsOn));
        Log::info('team_staff_moved', ['move_id' => $move->id, 'from_tenant_id' => $originId, 'to_tenant_id' => $destination->id]);

        return $move;
    }

    private function owner(): User
    {
        $user = Auth::user();
        if (! $user instanceof User || $user->role !== UserRole::Owner) {
            throw new OwnerRequiredException;
        }

        return $user;
    }

    /** @param list<int> $serviceIds Every one must be a service of the destination (each unit has its own prices). */
    private function ensureDestinationServices(array $serviceIds): void
    {
        $offered = collect($this->showSchedule->handle()->services->all())->pluck('id')->map(fn ($id): int => (int) $id)->all();
        if (array_diff($serviceIds, $offered) !== []) {
            throw new StaffServiceUnavailableException;
        }
    }
}
