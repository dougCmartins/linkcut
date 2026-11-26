<?php

declare(strict_types=1);

namespace Domain\Team\Actions;

use Domain\Team\Data\PublicStaffData;
use Domain\Team\Models\Staff;
use Domain\Team\Support\StaffServiceCoverage;
use Spatie\LaravelData\DataCollection;

/** Active barbers shown on the public booking picker; with services, only those who do all of them. */
final class ListPublicStaff
{
    /**
     * @param  list<int>  $serviceIds
     * @return DataCollection<int, PublicStaffData>
     */
    public function handle(array $serviceIds = []): DataCollection
    {
        $staff = Staff::query()
            ->where('active', true)
            ->orderBy('id')
            ->get(['id', 'name', 'avatar']);
        $eligible = array_flip(StaffServiceCoverage::filter(
            $staff->pluck('id')->map(fn ($id): int => (int) $id)->all(),
            $serviceIds,
        ));

        $members = $staff
            ->filter(fn (Staff $member): bool => isset($eligible[$member->id]))
            ->values()
            ->map(fn (Staff $member): PublicStaffData => new PublicStaffData(
                id: $member->id,
                name: $member->name,
                avatar: $member->avatar,
            ));

        return PublicStaffData::collect($members, DataCollection::class);
    }
}
