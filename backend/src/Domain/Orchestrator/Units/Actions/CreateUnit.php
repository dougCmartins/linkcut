<?php

declare(strict_types=1);

namespace Domain\Orchestrator\Units\Actions;

use Domain\Orchestrator\PublicPage\Support\BrandCache;
use Domain\Orchestrator\Units\Data\NewUnitData;
use Domain\Settings\Actions\LocateAddress;
use Domain\Settings\Actions\SetUpUnitSettings;
use Domain\Settings\Data\UnitSettingsData;
use Domain\Tenant\Actions\CreateTenant;
use Domain\Tenant\Actions\EnsureOrganizationOwner;
use Domain\Tenant\Actions\ListUnits;
use Domain\Tenant\Context\CurrentTenant;
use Domain\Tenant\Data\NewTenantData;
use Domain\Tenant\Data\OrganizationOwnerData;
use Domain\Tenant\Data\TenantData;
use Domain\Tenant\Data\UnitAccessData;
use Domain\Tenant\Data\UnitData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * "Nova filial", across domains (docs/decisoes/0001 P1): only the owner of the organization; the address is located
 * first (web service, outside the transaction); then, in one transaction, the unit (Tenant, which turns the brand
 * multi-unit) and its settings (Settings: defaults, or a one-time copy of the main unit). The branch starts in the
 * onboarding wizard, like a new sign-up.
 */
final class CreateUnit
{
    public function __construct(
        private readonly EnsureOrganizationOwner $ensureOwner,
        private readonly ListUnits $listUnits,
        private readonly LocateAddress $locateAddress,
        private readonly CreateTenant $createTenant,
        private readonly SetUpUnitSettings $setUpSettings,
        private readonly CurrentTenant $currentTenant,
        private readonly BrandCache $brandCache,
    ) {}

    public function handle(UnitAccessData $access, NewUnitData $data): UnitData
    {
        $organizationId = (int) $access->organization_id;
        $this->ensureOwner->handle(new OrganizationOwnerData(organization_id: $organizationId, user_id: $access->user_id));
        $main = collect($this->listUnits->handle($access)->units)->firstWhere('is_main', true);
        $address = $this->locateAddress->handle($data->address);

        $unit = DB::transaction(function () use ($data, $organizationId, $main, $address): TenantData {
            $unit = $this->createTenant->handle(new NewTenantData(name: trim($data->name), slug: $data->slug, organization_id: $organizationId));
            $this->currentTenant->run($unit->id, fn () => $this->setUpSettings->handle(new UnitSettingsData(
                name: trim($data->name),
                address: $address,
                copy_from_tenant_id: $data->copy_settings ? $main?->id : null,
            )));

            return $unit;
        });
        // The brand link (the main unit's slug) lists the new branch, and its page shows the picker, right away.
        if ($main !== null) {
            $this->brandCache->forget($main->slug, $main->id);
        }
        Log::info('organization_unit_created', ['organization_id' => $organizationId, 'tenant_id' => $unit->id, 'copied' => $data->copy_settings]);

        return new UnitData(id: $unit->id, name: $unit->name, slug: $unit->slug, is_main: false, active: $unit->active, onboarded: $unit->onboarded);
    }
}
