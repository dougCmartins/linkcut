<?php

declare(strict_types=1);

namespace Domain\Orchestrator\Onboarding\Actions;

use Domain\Identity\Actions\ConsumeSignUpCode;
use Domain\Identity\Actions\CreateOwner;
use Domain\Identity\Actions\IssueSession;
use Domain\Identity\Data\IssueSessionData;
use Domain\Identity\Data\NewOwnerData;
use Domain\Identity\Data\SessionData;
use Domain\Identity\Data\SignUpCodeCheckData;
use Domain\Identity\Data\VerifiedUserData;
use Domain\Orchestrator\Onboarding\Data\SignUpData;
use Domain\Settings\Actions\CreateDefaultSettings;
use Domain\Settings\Data\DefaultSettingsData;
use Domain\Tenant\Actions\CreateTenant;
use Domain\Tenant\Context\CurrentTenant;
use Domain\Tenant\Data\NewTenantData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Open sign-up, across domains: the code proves the e-mail (Identity) → in one transaction the barbershop (Tenant),
 * its owner (Identity) and its default settings with the barbershop name (Settings) → a session (Identity).
 * The e-mail was just confirmed, so this first session needs no 2FA.
 */
final class SignUpBarber
{
    public function __construct(
        private readonly ConsumeSignUpCode $consumeCode,
        private readonly CreateTenant $createTenant,
        private readonly CreateOwner $createOwner,
        private readonly CreateDefaultSettings $createDefaultSettings,
        private readonly CurrentTenant $currentTenant,
        private readonly IssueSession $issueSession,
    ) {}

    public function handle(SignUpData $data): SessionData
    {
        $this->consumeCode->handle(new SignUpCodeCheckData(email: $data->email, code: $data->code));

        $owner = DB::transaction(function () use ($data): VerifiedUserData {
            $tenant = $this->createTenant->handle(new NewTenantData(name: trim($data->barbershop_name), slug: $data->slug));
            $owner = $this->createOwner->handle(new NewOwnerData(
                tenant_id: $tenant->id,
                name: $data->name,
                email: $data->email,
                password: $data->password,
            ));
            $this->currentTenant->run(
                $tenant->id,
                fn () => $this->createDefaultSettings->handle(new DefaultSettingsData(name: trim($data->barbershop_name))),
            );

            return $owner;
        });
        Log::info('barber_signed_up', ['user_id' => $owner->user_id, 'tenant_id' => $owner->tenant_id]);

        return $this->issueSession->handle(new IssueSessionData(user_id: $owner->user_id));
    }
}
