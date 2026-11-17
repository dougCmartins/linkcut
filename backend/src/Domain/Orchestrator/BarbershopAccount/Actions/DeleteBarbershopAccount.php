<?php

declare(strict_types=1);

namespace Domain\Orchestrator\BarbershopAccount\Actions;

use Domain\Identity\Actions\DeleteTenantUsers;
use Domain\Identity\Actions\EnsureUserPassword;
use Domain\Identity\Data\UserPasswordConfirmationData;
use Domain\Orchestrator\BarbershopAccount\Data\DeleteBarbershopData;
use Domain\Settings\Actions\DeleteTenantImageFiles;
use Domain\Tenant\Actions\DeleteTenant;
use Domain\Tenant\Actions\ListOrganizationUnitIds;
use Domain\Tenant\Actions\ShowCurrentTenant;
use Domain\Tenant\Data\TenantIdData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * "Excluir barbearia" (LGPD): after the password and the link typed again, everything of the barbershop goes away:
 * barbers and their sessions, settings, images (files too), bookings, finance and push subscriptions. Clients'
 * accounts stay (they belong to the clients); their bookings at this barbershop go with it. Cannot be undone.
 * On the main unit it deletes the whole brand (every branch too); on a branch, only that branch.
 */
final class DeleteBarbershopAccount
{
    public function __construct(
        private readonly EnsureUserPassword $ensurePassword,
        private readonly ShowCurrentTenant $showTenant,
        private readonly DeleteTenantUsers $deleteUsers,
        private readonly DeleteTenant $deleteTenant,
        private readonly ListOrganizationUnitIds $listUnitIds,
        private readonly DeleteTenantImageFiles $deleteImageFiles,
    ) {}

    public function handle(DeleteBarbershopData $data): void
    {
        $this->ensurePassword->handle(new UserPasswordConfirmationData(password: $data->password));
        $tenant = $this->showTenant->handle();
        if (mb_strtolower(trim($data->slug_confirmation)) !== $tenant->slug) {
            throw ValidationException::withMessages(['slug_confirmation' => 'same']);
        }
        $current = new TenantIdData(tenant_id: $tenant->id);
        // Branches first, the main unit (the owner's own) last.
        $unitIds = $tenant->is_main
            ? [...array_values(array_diff($this->listUnitIds->handle($current)->ids, [$tenant->id])), $tenant->id]
            : [$tenant->id];
        $units = array_map(fn (int $id): TenantIdData => new TenantIdData(tenant_id: $id), $unitIds);

        DB::transaction(function () use ($units): void {
            foreach ($units as $unit) {
                $this->deleteUsers->handle($unit);
                $this->deleteTenant->handle($unit);
            }
        });
        // Files after the commit: if the database part failed, nothing is lost.
        foreach ($units as $unit) {
            $this->deleteImageFiles->handle($unit);
        }
        Log::channel('security')->warning('barbershop_deleted', ['tenant_id' => $tenant->id, 'units' => $unitIds]);
    }
}
