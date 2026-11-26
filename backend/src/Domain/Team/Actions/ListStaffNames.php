<?php

declare(strict_types=1);

namespace Domain\Team\Actions;

use Domain\Team\Models\Staff;

/** Names of barbers by id, in any unit (the client area lists bookings of several barbershops). */
final class ListStaffNames
{
    /**
     * @param  list<int>  $staffIds
     * @return array<int, string>
     */
    public function handle(array $staffIds): array
    {
        if ($staffIds === []) {
            return [];
        }

        return Staff::withoutTenantScope()
            ->whereIn('id', array_values(array_unique($staffIds)))
            ->pluck('name', 'id')
            ->mapWithKeys(fn (mixed $name, mixed $id): array => [(int) $id => (string) $name])
            ->all();
    }
}
