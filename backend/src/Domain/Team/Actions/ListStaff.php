<?php

declare(strict_types=1);

namespace Domain\Team\Actions;

use Domain\Team\Data\StaffActorData;
use Domain\Team\Data\StaffData;
use Domain\Team\Models\Staff;
use Domain\Team\Support\StaffDataMapper;

/** "Minha equipe": the barbers of the current unit, active first. Two queries for any team size. */
final class ListStaff
{
    /** @return list<StaffData> */
    public function handle(StaffActorData $actor): array
    {
        return Staff::query()->with('permissions')->orderByDesc('active')->orderBy('name')->get()
            ->map(fn (Staff $staff): StaffData => StaffDataMapper::toData($staff, $actor->user_id))
            ->values()
            ->all();
    }
}
