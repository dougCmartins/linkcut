<?php

declare(strict_types=1);

namespace Domain\Identity\Support;

use Domain\Identity\Data\UserData;
use Domain\Identity\Models\User;

/** One place that turns a User into the UserData the API returns (masked phone, no secrets). */
final class UserDataMapper
{
    public static function toData(User $user): UserData
    {
        return new UserData(
            id: $user->id,
            tenant_id: $user->tenant_id,
            name: $user->name,
            email: $user->email,
            phone_masked: $user->phone ? ContactMask::phone($user->phone) : null,
            role: $user->role,
            two_factor_channel: $user->two_factor_channel,
            available_channels: $user->availableTwoFactorChannels(),
        );
    }
}
