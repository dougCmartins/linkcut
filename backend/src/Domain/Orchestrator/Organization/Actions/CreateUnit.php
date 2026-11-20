<?php

declare(strict_types=1);

namespace Domain\Orchestrator\Organization\Actions;

use Domain\Orchestrator\Organization\Data\CreateUnitData;
use Domain\Orchestrator\Organization\Data\UnitData;
use Domain\Settings\Actions\CloneUnitSettings;
use Domain\Settings\Actions\CreateDefaultSettings;
use Domain\Settings\Data\DefaultSettingsData;
use Domain\Settings\Models\ProfileSettings;
use Domain\Tenant\Actions\CreateTenant;
use Domain\Tenant\Context\CurrentTenant;
use Domain\Tenant\Data\NewTenantData;
use Domain\Tenant\Enums\OrganizationType;
use Domain\Tenant\Exceptions\TenantNotResolvedException;
use Domain\Tenant\Models\Organization;
use Domain\Tenant\Models\Tenant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final class CreateUnit
{
    public function __construct(
        private readonly CreateTenant $createTenant,
        private readonly CreateDefaultSettings $createDefaultSettings,
        private readonly CloneUnitSettings $cloneUnitSettings,
        private readonly CurrentTenant $currentTenant,
    ) {}

    public function handle(CreateUnitData $data): UnitData
    {
        $user = Auth::user();
        $organizationId = $user?->organization_id;
        if (! $organizationId) {
            throw new TenantNotResolvedException;
        }

        $organization = Organization::query()->findOrFail($organizationId);
        $main = Tenant::query()
            ->where('organization_id', $organization->id)
            ->where('is_main', true)
            ->firstOrFail();

        return DB::transaction(function () use ($data, $organization, $main): UnitData {
            $name = trim($data->name);
            $tenant = $this->createTenant->handle(new NewTenantData(
                name: $name,
                slug: $data->slug,
                organization_id: $organization->id,
                is_main: false,
            ));
            Tenant::query()->whereKey($tenant->id)->update(['onboarded_at' => now()]);

            $this->currentTenant->run($tenant->id, function () use ($data, $name, $main): void {
                if ($data->copy_settings) {
                    $this->cloneUnitSettings->handle($main->id);
                } else {
                    $this->createDefaultSettings->handle(new DefaultSettingsData(name: $name));
                }

                ProfileSettings::query()->firstOrFail()->update([
                    'name' => $name,
                    'address_zip_code' => $data->address->zip_code,
                    'address_street' => trim($data->address->street),
                    'address_number' => trim($data->address->number),
                    'address_complement' => $data->address->complement ? trim($data->address->complement) : null,
                    'address_district' => trim($data->address->district),
                    'address_city' => trim($data->address->city),
                    'address_state' => $data->address->state,
                    'latitude' => $data->address->latitude,
                    'longitude' => $data->address->longitude,
                ]);
            });

            $unitCount = Tenant::query()->where('organization_id', $organization->id)->count();
            if ($unitCount >= 2 && $organization->type !== OrganizationType::MultiUnit) {
                $organization->update(['type' => OrganizationType::MultiUnit]);
            }

            return new UnitData(
                id: $tenant->id,
                name: $name,
                slug: $tenant->slug,
                is_main: false,
            );
        });
    }
}
