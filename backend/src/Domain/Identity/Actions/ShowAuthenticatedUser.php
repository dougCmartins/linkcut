<?php

declare(strict_types=1);

namespace Domain\Identity\Actions;

use Domain\Identity\Data\UserData;
use Domain\Identity\Models\User;
use Domain\Identity\Support\UserDataMapper;

final class ShowAuthenticatedUser
{
    public function handle(): UserData
    {
        /** @var User $user */
        $user = auth('api')->user();

        return UserDataMapper::toData($user);
    }
}
