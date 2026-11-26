<?php

declare(strict_types=1);

namespace Database\Seeders;

use Domain\Identity\Enums\UserRole;
use Domain\Identity\Models\User;
use Domain\Settings\Actions\CreateDefaultSettings;
use Domain\Settings\Data\DefaultSettingsData;
use Domain\Settings\Models\ProfileSettings;
use Domain\Team\Actions\CreateOwnerStaff;
use Domain\Team\Data\NewOwnerStaffData;
use Domain\Team\Models\Staff;
use Domain\Team\Models\StaffPermission;
use Domain\Team\Support\SeedStaffSchedule;
use Domain\Tenant\Context\CurrentTenant;
use Domain\Tenant\Enums\OrganizationType;
use Domain\Tenant\Models\Organization;
use Domain\Tenant\Models\Tenant;

/**
 * A brand with a branch, to test units and team by hand (idempotent, demo only).
 * - Owner: dona@cortefino.app / linkcut123 (main unit /corte-fino, branch /corte-fino-paulista).
 * - Barber of the branch: barbeiro@cortefino.app / linkcut123 (sees only their own appointments).
 * The branch has two active barbers (the owner and the barber), so the booking shows "Escolha o barbeiro".
 */
final class BranchDemoSeeder
{
    private const PASSWORD = 'linkcut123';

    public function run(): void
    {
        $organization = Organization::query()->updateOrCreate(
            ['slug' => 'corte-fino'],
            ['name' => 'Corte Fino', 'type' => OrganizationType::MultiUnit],
        );
        $main = $this->unit($organization, 'corte-fino', 'Corte Fino', true, ['01304001', 'Rua Augusta', '900', 'Consolação', 'São Paulo', 'SP', -23.5540, -46.6580]);
        $branch = $this->unit($organization, 'corte-fino-paulista', 'Corte Fino Paulista', false, ['01310100', 'Avenida Paulista', '1578', 'Bela Vista', 'São Paulo', 'SP', -23.5614, -46.6559]);

        $owner = User::query()->updateOrCreate(['email' => 'dona@cortefino.app'], [
            'tenant_id' => $main->id,
            'organization_id' => $organization->id,
            'role' => UserRole::Owner,
            'name' => 'Carla Mendes',
            'password' => self::PASSWORD,
        ]);
        $organization->update(['owner_user_id' => $owner->id]);
        foreach ([$main, $branch] as $unit) {
            app(CreateOwnerStaff::class)->handle(new NewOwnerStaffData(tenant_id: $unit->id, user_id: $owner->id, name: $owner->name));
        }

        $barber = User::query()->updateOrCreate(['email' => 'barbeiro@cortefino.app'], [
            'tenant_id' => $branch->id,
            'organization_id' => $organization->id,
            'role' => UserRole::Barber,
            'name' => 'Diego Alves',
            'password' => self::PASSWORD,
        ]);
        app(CurrentTenant::class)->run($branch->id, function () use ($barber, $branch): void {
            $staff = Staff::query()->firstOrCreate(['user_id' => $barber->id], ['name' => $barber->name, 'active' => true]);
            StaffPermission::query()->firstOrCreate(['staff_id' => $staff->id]);
            SeedStaffSchedule::forStaff($branch->id, $staff->id);
        });
    }

    /** @param array{0: string, 1: string, 2: string, 3: string, 4: string, 5: string, 6: float, 7: float} $address */
    private function unit(Organization $organization, string $slug, string $name, bool $isMain, array $address): Tenant
    {
        $tenant = Tenant::query()->updateOrCreate(['slug' => $slug], [
            'organization_id' => $organization->id,
            'is_main' => $isMain,
            'name' => $name,
            'active' => true,
            'onboarded_at' => now(),
        ]);

        app(CurrentTenant::class)->run($tenant->id, function () use ($name, $address): void {
            if (ProfileSettings::query()->exists()) {
                return;
            }
            app(CreateDefaultSettings::class)->handle(new DefaultSettingsData(name: $name));
            [$zipCode, $street, $number, $district, $city, $state, $latitude, $longitude] = $address;
            ProfileSettings::query()->firstOrFail()->update([
                'whatsapp_number' => '11955554444',
                'address_zip_code' => $zipCode,
                'address_street' => $street,
                'address_number' => $number,
                'address_district' => $district,
                'address_city' => $city,
                'address_state' => $state,
                'latitude' => $latitude,
                'longitude' => $longitude,
            ]);
        });

        return $tenant;
    }
}
