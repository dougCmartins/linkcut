<?php

declare(strict_types=1);

namespace Domain\Team\Actions;

use Domain\Team\Data\StaffIdData;
use Domain\Team\Exceptions\StaffNotFoundException;
use Domain\Team\Models\Staff;
use Domain\Team\Models\StaffTimeBlock;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class DeleteStaffTimeBlock
{
    public function handle(StaffIdData $staff, int $blockId): void
    {
        Staff::query()->whereKey($staff->id)->first() ?? throw new StaffNotFoundException;

        $deleted = StaffTimeBlock::query()
            ->where('staff_id', $staff->id)
            ->whereKey($blockId)
            ->delete();

        if ($deleted === 0) {
            throw new NotFoundHttpException;
        }
    }
}
