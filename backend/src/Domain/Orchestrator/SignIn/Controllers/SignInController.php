<?php

declare(strict_types=1);

namespace Domain\Orchestrator\SignIn\Controllers;

use Domain\Identity\Data\SessionData;
use Domain\Identity\Data\VerifyTwoFactorCodeData;
use Domain\Orchestrator\SignIn\Actions\CompleteSignIn;

final class SignInController
{
    public function verify(VerifyTwoFactorCodeData $data, CompleteSignIn $action): SessionData
    {
        return $action->handle($data);
    }
}
