<?php

declare(strict_types=1);

namespace Domain\Team\Actions;

use Domain\Team\Enums\StaffMoveKind;
use Domain\Team\Enums\StaffMoveStatus;
use Domain\Team\Models\StaffMove;

/** Active loans whose last day is before the given day (candidates for the automatic return). */
final class ListDueLoans
{
    /** @return list<array{id: int, organization_id: int}> */
    public function handle(string $beforeDay): array
    {
        return StaffMove::query()
            ->where('kind', StaffMoveKind::Loan)
            ->where('status', StaffMoveStatus::Active)
            ->where('ends_on', '<', $beforeDay)
            ->orderBy('id')
            ->get(['id', 'organization_id'])
            ->map(fn (StaffMove $move): array => ['id' => $move->id, 'organization_id' => (int) $move->organization_id])
            ->all();
    }
}
