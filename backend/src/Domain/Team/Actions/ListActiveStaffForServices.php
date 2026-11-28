<?php

declare(strict_types=1);

namespace Domain\Team\Actions;

use Domain\Team\Models\Staff;
use Domain\Team\Support\StaffServiceCoverage;

/**
 * Active barbers of the current unit who can perform every given service. With a day, a borrowed barber whose loan
 * ends before it is left out, in the same query (docs/decisoes/0003).
 *
 * @return list<int> staff ids ascending
 */
final class ListActiveStaffForServices
{
    /** @param list<int> $serviceIds */
    public function handle(array $serviceIds, ?string $onDay = null): array
    {
        $staffIds = Staff::query()->where('active', true)
            ->when($onDay !== null, fn ($query) => $query->bookableOn($onDay))
            ->orderBy('id')->pluck('id')->map(fn ($id): int => (int) $id)->all();

        return StaffServiceCoverage::filter($staffIds, $serviceIds);
    }
}
