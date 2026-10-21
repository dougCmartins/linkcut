<?php

declare(strict_types=1);

namespace Domain\Orchestrator\Onboarding\Controllers;

use Domain\Identity\Actions\SendSignUpCode;
use Domain\Identity\Data\SessionData;
use Domain\Identity\Data\SignUpCodeRequestData;
use Domain\Identity\Data\SignUpCodeSentData;
use Domain\Orchestrator\Onboarding\Actions\SignUpBarber;
use Domain\Orchestrator\Onboarding\Data\SignUpData;
use Domain\Orchestrator\Onboarding\Data\SlugQueryData;
use Domain\Tenant\Actions\CheckSlugAvailability;
use Domain\Tenant\Data\SlugAvailabilityData;
use Domain\Tenant\Data\TenantSlugData;

final class SignUpController
{
    public function sendCode(SignUpCodeRequestData $data, SendSignUpCode $action): SignUpCodeSentData
    {
        return $action->handle($data);
    }

    public function slug(SlugQueryData $data, CheckSlugAvailability $action): SlugAvailabilityData
    {
        return $action->handle(new TenantSlugData(slug: $data->slug));
    }

    public function signUp(SignUpData $data, SignUpBarber $action): SessionData
    {
        return $action->handle($data);
    }
}
