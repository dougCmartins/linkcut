<?php

declare(strict_types=1);

namespace Domain\Team\Actions;

use Domain\Team\Models\Staff;

/** The login of a barber of the current unit (null while the invitation is not accepted). */
final class ShowStaffUserId
{
    public function handle(int $staffId): ?int
    {
        $userId = Staff::query()->whereKey($staffId)->value('user_id');

        return $userId === null ? null : (int) $userId;
    }
}
