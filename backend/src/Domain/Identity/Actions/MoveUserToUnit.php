<?php

declare(strict_types=1);

namespace Domain\Identity\Actions;

use Domain\Identity\Data\UserUnitData;
use Domain\Identity\Models\User;

/** The barber signs in to another unit from now on (the dashboard opens there; the other one is closed to them). */
final class MoveUserToUnit
{
    public function handle(UserUnitData $data): void
    {
        User::query()->whereKey($data->user_id)->update(['tenant_id' => $data->tenant_id]);
    }
}
