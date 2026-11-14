<?php

declare(strict_types=1);

namespace Domain\Orchestrator\SignIn\Actions;

use Domain\Identity\Actions\StartLogin;
use Domain\Identity\Data\LoginChallengeData;
use Domain\Identity\Data\LoginData;
use Domain\Orchestrator\SignIn\Data\BarberLoginData;
use Domain\Tenant\Actions\FindActiveTenantBySlug;
use Domain\Tenant\Data\TenantSlugData;
use Domain\Tenant\Exceptions\TenantNotFoundException;

/**
 * Step 1 of sign-in, across domains: the barbershop of the page (Tenant) → password of one of its barbers (Identity).
 * An unknown or inactive page fails exactly like a wrong password. Without a page (/entrar), the e-mail alone says
 * which barbershop it is (e-mails are unique).
 */
final class StartBarberLogin
{
    public function __construct(
        private readonly FindActiveTenantBySlug $findTenant,
        private readonly StartLogin $startLogin,
    ) {}

    public function handle(BarberLoginData $data): LoginChallengeData
    {
        $credentials = new LoginData(email: $data->email, password: $data->password);
        if ($data->slug === null || $data->slug === '') {
            return $this->startLogin->handle($credentials, tenantId: null, anyBarbershop: true);
        }

        try {
            $tenantId = $this->findTenant->handle(TenantSlugData::from(['slug' => mb_strtolower($data->slug)]))->id;
        } catch (TenantNotFoundException) {
            $tenantId = null;
        }

        return $this->startLogin->handle($credentials, $tenantId);
    }
}
