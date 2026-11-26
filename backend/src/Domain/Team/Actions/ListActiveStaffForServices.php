<?php

declare(strict_types=1);

namespace Domain\Team\Actions;

use Domain\Team\Models\Staff;
use Domain\Team\Support\StaffServiceCoverage;

/**
 * Active barbers of the current unit who can perform every given service.
 *
 * @return list<int> staff ids ascending
 */
final class ListActiveStaffForServices
{
    /** @param list<int> $serviceIds */
    public function handle(array $serviceIds): array
    {
        $staffIds = Staff::query()->where('active', true)->orderBy('id')->pluck('id')->map(fn ($id): int => (int) $id)->all();

        return StaffServiceCoverage::filter($staffIds, $serviceIds);
    }
}
