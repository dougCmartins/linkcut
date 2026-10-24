<?php

declare(strict_types=1);

namespace Domain\Orchestrator\BarbershopAccount\Actions;

use Domain\Identity\Actions\DeleteTenantUsers;
use Domain\Identity\Actions\EnsureUserPassword;
use Domain\Identity\Data\UserPasswordConfirmationData;
use Domain\Orchestrator\BarbershopAccount\Data\DeleteBarbershopData;
use Domain\Settings\Actions\DeleteTenantImageFiles;
use Domain\Tenant\Actions\DeleteTenant;
use Domain\Tenant\Actions\ShowCurrentTenant;
use Domain\Tenant\Data\TenantIdData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * "Excluir barbearia" (LGPD): after the password and the link typed again, everything of the barbershop goes away:
 * barbers and their sessions, settings, images (files too), bookings, finance and push subscriptions. Clients'
 * accounts stay (they belong to the clients); their bookings at this barbershop go with it. Cannot be undone.
 */
final class DeleteBarbershopAccount
{
    public function __construct(
        private readonly EnsureUserPassword $ensurePassword,
        private readonly ShowCurrentTenant $showTenant,
        private readonly DeleteTenantUsers $deleteUsers,
        private readonly DeleteTenant $deleteTenant,
        private readonly DeleteTenantImageFiles $deleteImageFiles,
    ) {}

    public function handle(DeleteBarbershopData $data): void
    {
        $this->ensurePassword->handle(new UserPasswordConfirmationData(password: $data->password));
        $tenant = $this->showTenant->handle();
        if (mb_strtolower(trim($data->slug_confirmation)) !== $tenant->slug) {
            throw ValidationException::withMessages(['slug_confirmation' => 'same']);
        }
        $tenantId = new TenantIdData(tenant_id: $tenant->id);

        DB::transaction(function () use ($tenantId): void {
            $this->deleteUsers->handle($tenantId);
            $this->deleteTenant->handle($tenantId);
        });
        // Files after the commit: if the database part failed, nothing is lost.
        $this->deleteImageFiles->handle($tenantId);
        Log::channel('security')->warning('barbershop_deleted', ['tenant_id' => $tenant->id]);
    }
}
