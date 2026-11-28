<?php

declare(strict_types=1);

namespace Domain\Team\Actions;

use Domain\Team\Data\ActiveStaffMoveData;
use Domain\Team\Enums\StaffMoveStatus;
use Domain\Team\Models\Staff;
use Domain\Team\Models\StaffMove;

/** The brand's current moves (active loans and transfers), newest first. Two queries for any number of moves. */
final class ListActiveStaffMoves
{
    /** @return list<ActiveStaffMoveData> */
    public function handle(int $organizationId): array
    {
        $moves = StaffMove::query()->where('organization_id', $organizationId)->where('status', StaffMoveStatus::Active)
            ->orderByDesc('id')->get();
        $rows = Staff::withoutTenantScope()
            ->whereKey($moves->flatMap(fn (StaffMove $move): array => [$move->from_staff_id, $move->to_staff_id])->unique()->values()->all())
            ->get(['id', 'tenant_id', 'name'])
            ->keyBy('id');

        return $moves->map(fn (StaffMove $move): ActiveStaffMoveData => new ActiveStaffMoveData(
            id: $move->id,
            kind: $move->kind->value,
            starts_on: $move->starts_on->toDateString(),
            ends_on: $move->ends_on?->toDateString(),
            barber_name: (string) $rows[$move->to_staff_id]?->name,
            from_unit_id: (int) $rows[$move->from_staff_id]?->tenant_id,
            to_unit_id: (int) $rows[$move->to_staff_id]?->tenant_id,
        ))->values()->all();
    }
}
