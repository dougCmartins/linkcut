<?php

declare(strict_types=1);

namespace Domain\Orchestrator\SignIn\Controllers;

use Domain\Identity\Data\EmailData;
use Domain\Identity\Data\LoginChallengeData;
use Domain\Identity\Data\SessionData;
use Domain\Identity\Data\VerifyTwoFactorCodeData;
use Domain\Orchestrator\SignIn\Actions\CompleteSignIn;
use Domain\Orchestrator\SignIn\Actions\SendLoginLink;
use Domain\Orchestrator\SignIn\Actions\StartBarberLogin;
use Domain\Orchestrator\SignIn\Data\BarberLoginData;
use Illuminate\Http\JsonResponse;

final class SignInController
{
    public function login(BarberLoginData $data, StartBarberLogin $action): LoginChallengeData
    {
        return $action->handle($data);
    }

    /** Same answer for any e-mail (no account enumeration). */
    public function sendLink(EmailData $data, SendLoginLink $action): JsonResponse
    {
        $action->handle($data);

        return response()->json(['sent' => true], 202);
    }

    public function verify(VerifyTwoFactorCodeData $data, CompleteSignIn $action): SessionData
    {
        return $action->handle($data);
    }
}
