<?php

declare(strict_types=1);

namespace Domain\Team\Actions;

use Domain\Team\Data\PublicStaffData;
use Domain\Team\Models\Staff;
use Spatie\LaravelData\DataCollection;

/** Active barbers shown on the public booking picker. */
final class ListPublicStaff
{
    /** @return DataCollection<int, PublicStaffData> */
    public function handle(): DataCollection
    {
        $staff = Staff::query()
            ->where('active', true)
            ->orderBy('id')
            ->get(['id', 'name', 'avatar'])
            ->map(fn (Staff $member): PublicStaffData => new PublicStaffData(
                id: $member->id,
                name: $member->name,
                avatar: $member->avatar,
            ));

        return PublicStaffData::collect($staff, DataCollection::class);
    }
}
