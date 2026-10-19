<?php

declare(strict_types=1);

namespace Domain\Orchestrator\SignIn\Actions;

use Domain\Identity\Actions\IssueSession;
use Domain\Identity\Actions\VerifyTwoFactorCode;
use Domain\Identity\Data\IssueSessionData;
use Domain\Identity\Data\SessionData;
use Domain\Identity\Data\VerifyTwoFactorCodeData;
use Domain\Tenant\Actions\EnsureTenantIsActive;
use Domain\Tenant\Data\TenantIdData;
use Illuminate\Support\Facades\Log;

/**
 * Last step of sign-in, across domains: right code (Identity) → tenant still
 * active (Tenant) → session (Identity). No model here, only Actions.
 */
final class CompleteSignIn
{
    public function __construct(
        private readonly VerifyTwoFactorCode $verifyTwoFactorCode,
        private readonly EnsureTenantIsActive $ensureTenantIsActive,
        private readonly IssueSession $issueSession,
    ) {}

    public function handle(VerifyTwoFactorCodeData $data): SessionData
    {
        $verified = $this->verifyTwoFactorCode->handle($data);
        $this->ensureTenantIsActive->handle(new TenantIdData(tenant_id: $verified->tenant_id));
        $session = $this->issueSession->handle(new IssueSessionData(user_id: $verified->user_id));

        Log::info('user_authenticated', ['user_id' => $verified->user_id, 'tenant_id' => $verified->tenant_id]);

        return $session;
    }
}
