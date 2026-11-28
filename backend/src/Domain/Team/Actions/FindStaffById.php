<?php

declare(strict_types=1);

namespace Domain\Team\Actions;

use Domain\Team\Data\StaffData;
use Domain\Team\Data\StaffIdData;
use Domain\Team\Exceptions\StaffNotFoundException;
use Domain\Team\Models\Staff;
use Domain\Team\Support\StaffDataMapper;

final class FindStaffById
{
    /** With a day: a borrowed barber whose loan ends before it is not found (docs/decisoes/0003), same query. */
    public function handle(StaffIdData $data, ?string $bookableOn = null): StaffData
    {
        $staff = Staff::query()->whereKey($data->id)
            ->when($bookableOn !== null, fn ($query) => $query->bookableOn($bookableOn))
            ->with('permissions')->first()
            ?? throw new StaffNotFoundException;

        return StaffDataMapper::toData($staff);
    }
}
