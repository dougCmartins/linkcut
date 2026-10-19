<?php

declare(strict_types=1);

namespace Domain\Identity\Actions;

use Domain\Identity\Data\UpdateTwoFactorChannelData;
use Domain\Identity\Data\UserData;
use Domain\Identity\Enums\TwoFactorChannel;
use Domain\Identity\Exceptions\ChannelUnavailableException;
use Domain\Identity\Models\User;
use Domain\Identity\Support\UserDataMapper;

/** Default channel for the 2FA code (Settings > Segurança). Null = ask every time. */
final class UpdateTwoFactorChannel
{
    public function handle(UpdateTwoFactorChannelData $data): UserData
    {
        /** @var User $user */
        $user = auth('api')->user();
        if ($data->channel === TwoFactorChannel::Sms && ! $user->phone) {
            throw new ChannelUnavailableException;
        }

        $user->update(['two_factor_channel' => $data->channel]);

        return UserDataMapper::toData($user);
    }
}
