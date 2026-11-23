<?php

declare(strict_types=1);

namespace Domain\Team\Actions;

use Domain\Team\Models\Staff;
use Domain\Team\Models\StaffService;

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
        $staff = Staff::query()->where('active', true)->orderBy('id')->get(['id']);
        if ($staff->isEmpty()) {
            return [];
        }

        if ($serviceIds === []) {
            return $staff->pluck('id')->map(fn ($id): int => (int) $id)->all();
        }

        $eligible = [];
        foreach ($staff as $member) {
            $linked = StaffService::query()->where('staff_id', $member->id)->pluck('service_id')->map(fn ($id): int => (int) $id)->all();
            if ($linked === []) {
                $eligible[] = (int) $member->id;

                continue;
            }
            $missing = array_diff($serviceIds, $linked);
            if ($missing === []) {
                $eligible[] = (int) $member->id;
            }
        }

        return $eligible;
    }
}
