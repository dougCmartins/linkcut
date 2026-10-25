<?php

declare(strict_types=1);

namespace Domain\Orchestrator\SignIn\Actions;

use Domain\Identity\Actions\FindUserTenant;
use Domain\Identity\Data\EmailData;
use Domain\Identity\Notifications\LoginLinkNotification;
use Domain\Identity\Support\ContactMask;
use Domain\Tenant\Actions\ListTenantsByIds;
use Domain\Tenant\Data\TenantIdsData;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * "Encontrar minha página": e-mails the sign-in link of the barber's own page. The answer is the same for any
 * e-mail, so nobody learns which ones have an account.
 */
final class SendLoginLink
{
    public function __construct(
        private readonly FindUserTenant $findUserTenant,
        private readonly ListTenantsByIds $listTenants,
    ) {}

    public function handle(EmailData $data): void
    {
        $email = mb_strtolower($data->email);
        $tenantId = $this->findUserTenant->handle(new EmailData(email: $email));
        $tenant = $tenantId ? $this->listTenants->handle(new TenantIdsData([$tenantId]))->first() : null;
        if (! $tenant || ! $tenant->active) {
            Log::info('login_link_not_sent', ['email' => ContactMask::email($email)]);

            return;
        }

        $url = rtrim((string) config('app.frontend_url'), '/')."/{$tenant->slug}/entrar";
        Notification::route('mail', $email)->notify(new LoginLinkNotification($tenant->name, $url));
        Log::info('login_link_sent', ['tenant_id' => $tenant->id]);
    }
}
