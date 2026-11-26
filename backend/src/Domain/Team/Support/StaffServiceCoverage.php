<?php

declare(strict_types=1);

namespace Domain\Team\Support;

use Domain\Team\Models\StaffService;

/** Which barbers do every asked service (staff_services). A barber without any link row is kept, as before. */
final class StaffServiceCoverage
{
    /**
     * @param  list<int>  $staffIds
     * @param  list<int>  $serviceIds
     * @return list<int> the staff ids (same order) that cover every service
     */
    public static function filter(array $staffIds, array $serviceIds): array
    {
        if ($staffIds === [] || $serviceIds === []) {
            return $staffIds;
        }

        /** @var array<int, list<int>> $linked */
        $linked = [];
        StaffService::query()
            ->whereIn('staff_id', $staffIds)
            ->get(['staff_id', 'service_id'])
            ->each(function (StaffService $link) use (&$linked): void {
                $linked[(int) $link->staff_id][] = (int) $link->service_id;
            });

        return array_values(array_filter(
            $staffIds,
            fn (int $staffId): bool => ! isset($linked[$staffId]) || array_diff($serviceIds, $linked[$staffId]) === [],
        ));
    }
}
