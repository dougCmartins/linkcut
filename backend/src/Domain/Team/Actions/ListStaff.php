<?php

declare(strict_types=1);

namespace Domain\Team\Actions;

use Domain\Team\Data\StaffData;
use Domain\Team\Models\Staff;
use Domain\Team\Support\StaffDataMapper;
use Spatie\LaravelData\DataCollection;

final class ListStaff
{
    /** @return DataCollection<int, StaffData> */
    public function handle(?int $onlyUserId = null): DataCollection
    {
        $query = Staff::query()->with('permissions')->orderBy('id');
        if ($onlyUserId !== null) {
            $query->where('user_id', $onlyUserId);
        }

        $rows = $query->get()->map(fn (Staff $staff): StaffData => StaffDataMapper::toData($staff));

        return StaffData::collect($rows, DataCollection::class);
    }
}
