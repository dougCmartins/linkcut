<?php

declare(strict_types=1);

namespace Domain\Orchestrator\StaffMove\Actions;

use Domain\Identity\Enums\UserRole;
use Domain\Identity\Models\User;
use Domain\Orchestrator\StaffMove\Data\BrandMoveData;
use Domain\Orchestrator\StaffMove\Data\MoveOptionsData;
use Domain\Orchestrator\StaffMove\Data\MoveServiceData;
use Domain\Orchestrator\StaffMove\Exceptions\MoveDestinationInvalidException;
use Domain\Settings\Actions\ShowScheduleSettings;
use Domain\Settings\Data\BusinessHourData;
use Domain\Settings\Data\ServiceData;
use Domain\Team\Actions\ListActiveStaffMoves;
use Domain\Team\Actions\ShowMovableStaff;
use Domain\Team\Data\ActiveStaffMoveData;
use Domain\Team\Data\StaffBusinessHourData;
use Domain\Team\Data\StaffIdData;
use Domain\Team\Exceptions\OwnerRequiredException;
use Domain\Tenant\Actions\IsUnitOfOrganization;
use Domain\Tenant\Actions\ListTenantsByIds;
use Domain\Tenant\Context\CurrentTenant;
use Domain\Tenant\Data\OrganizationUnitData;
use Domain\Tenant\Data\TenantData;
use Domain\Tenant\Data\TenantIdsData;
use Illuminate\Support\Facades\Auth;

/**
 * The reads behind the move screen (docs/decisoes/0003 P3), owner only: the destination's hours and services to start
 * the form with (Settings of another unit of the brand), and the brand's current moves with the unit names.
 */
final class ShowMoveOptions
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
        private readonly IsUnitOfOrganization $isUnitOfOrganization,
        private readonly ShowMovableStaff $showMovableStaff,
        private readonly ShowScheduleSettings $showSchedule,
        private readonly ListActiveStaffMoves $listMoves,
        private readonly ListTenantsByIds $listTenants,
    ) {}

    public function options(StaffIdData $staffId, int $toUnitId): MoveOptionsData
    {
        $owner = $this->owner();
        $isSameBrand = $this->isUnitOfOrganization->handle(new OrganizationUnitData(organization_id: (int) $owner->organization_id, unit_id: $toUnitId));
        if ($toUnitId === $this->currentTenant->id() || ! $isSameBrand) {
            throw new MoveDestinationInvalidException;
        }
        $this->showMovableStaff->handle($staffId);
        $schedule = $this->currentTenant->run($toUnitId, fn () => $this->showSchedule->handle());

        return new MoveOptionsData(
            weekly_hours: array_map(fn (BusinessHourData $hour): StaffBusinessHourData => new StaffBusinessHourData(
                weekday: $hour->weekday,
                is_open: $hour->is_open,
                opens_at: $hour->opens_at,
                closes_at: $hour->closes_at,
            ), $schedule->weekly_hours->all()),
            services: array_map(fn (ServiceData $service): MoveServiceData => new MoveServiceData(id: $service->id, name: $service->name), $schedule->services->all()),
        );
    }

    /** @return list<BrandMoveData> */
    public function moves(): array
    {
        $moves = $this->listMoves->handle((int) $this->owner()->organization_id);
        $unitIds = array_values(array_unique(array_merge(...array_map(fn (ActiveStaffMoveData $move): array => [$move->from_unit_id, $move->to_unit_id], $moves ?: []))));
        $names = collect($unitIds === [] ? [] : $this->listTenants->handle(new TenantIdsData(ids: $unitIds))->all())
            ->mapWithKeys(fn (TenantData $unit): array => [$unit->id => $unit->name]);

        return array_map(fn (ActiveStaffMoveData $move): BrandMoveData => new BrandMoveData(
            id: $move->id,
            kind: $move->kind,
            starts_on: $move->starts_on,
            ends_on: $move->ends_on,
            barber_name: $move->barber_name,
            from_unit_id: $move->from_unit_id,
            from_unit_name: (string) $names->get($move->from_unit_id, ''),
            to_unit_id: $move->to_unit_id,
            to_unit_name: (string) $names->get($move->to_unit_id, ''),
        ), $moves);
    }

    private function owner(): User
    {
        $user = Auth::user();
        if (! $user instanceof User || $user->role !== UserRole::Owner) {
            throw new OwnerRequiredException;
        }

        return $user;
    }
}
