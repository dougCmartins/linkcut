<?php

declare(strict_types=1);

namespace Domain\Team\Actions;

use Domain\Team\Data\StaffIdData;
use Domain\Team\Models\StaffService;

final class ListStaffServiceIds
{
    /** @return list<int> */
    public function handle(StaffIdData $data): array
    {
        return StaffService::query()
            ->where('staff_id', $data->id)
            ->orderBy('service_id')
            ->pluck('service_id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }
}
