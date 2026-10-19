<?php

declare(strict_types=1);

namespace Domain\Identity\Controllers;

use Domain\Identity\Actions\Logout;
use Domain\Identity\Actions\RefreshSession;
use Domain\Identity\Actions\ShowAuthenticatedUser;
use Domain\Identity\Actions\StartLogin;
use Domain\Identity\Actions\UpdateTwoFactorChannel;
use Domain\Identity\Data\LoginChallengeData;
use Domain\Identity\Data\LoginData;
use Domain\Identity\Data\LogoutData;
use Domain\Identity\Data\RefreshSessionData;
use Domain\Identity\Data\SessionData;
use Domain\Identity\Data\UpdateTwoFactorChannelData;
use Domain\Identity\Data\UserData;
use Illuminate\Http\Response;

final class AuthController
{
    public function login(LoginData $data, StartLogin $action): LoginChallengeData
    {
        return $action->handle($data);
    }

    public function refresh(RefreshSessionData $data, RefreshSession $action): SessionData
    {
        return $action->handle($data);
    }

    public function logout(LogoutData $data, Logout $action): Response
    {
        $action->handle($data);

        return response()->noContent();
    }

    public function me(ShowAuthenticatedUser $action): UserData
    {
        return $action->handle();
    }

    public function updateTwoFactorChannel(UpdateTwoFactorChannelData $data, UpdateTwoFactorChannel $action): UserData
    {
        return $action->handle($data);
    }
}
