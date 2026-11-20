<?php

declare(strict_types=1);

namespace Domain\Orchestrator\Organization\Tests\Feature;

use Domain\Catalog\Models\Product;
use Domain\Identity\Models\User;
use Domain\Settings\Actions\CreateDefaultSettings;
use Domain\Settings\Data\DefaultSettingsData;
use Domain\Settings\Models\ProfileSettings;
use Domain\Settings\Models\Service;
use Domain\Tenant\Context\CurrentTenant;
use Domain\Tenant\Enums\OrganizationType;
use Domain\Tenant\Models\Organization;
use Domain\Tenant\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

final class CreateUnitTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_second_unit_turns_the_organization_into_multi_unit(): void
    {
        // Arrange
        [$user, $main] = $this->individualOwnerWithSettings();
        Passport::actingAs($user);

        // Act
        $response = $this->postJson('/api/organization/units', $this->unitPayload('filial-centro', 'Filial Centro'));

        // Assert
        $response->assertCreated();
        $response->assertJsonPath('slug', 'filial-centro');
        $response->assertJsonPath('is_main', false);
        $this->assertSame(OrganizationType::MultiUnit, Organization::query()->findOrFail($user->organization_id)->type);
        $branchId = (int) $response->json('id');
        $this->assertNotSame($main->id, $branchId);
        app(CurrentTenant::class)->run($branchId, function (): void {
            $this->assertSame('Filial Centro', ProfileSettings::query()->firstOrFail()->name);
            $this->assertSame('01304001', ProfileSettings::query()->firstOrFail()->address_zip_code);
        });
    }

    public function test_copy_settings_clones_services_from_the_main_unit_once(): void
    {
        // Arrange
        [$user, $main] = $this->individualOwnerWithSettings();
        app(CurrentTenant::class)->run($main->id, function (): void {
            Service::query()->where('name', 'Corte')->update(['price_in_cents' => 5500]);
        });
        Passport::actingAs($user);

        // Act
        $response = $this->postJson('/api/organization/units', $this->unitPayload('filial-copy', 'Filial Copy', copySettings: true));

        // Assert
        $response->assertCreated();
        $branchId = (int) $response->json('id');
        app(CurrentTenant::class)->run($branchId, function (): void {
            $this->assertSame(5500, Service::query()->where('name', 'Corte')->value('price_in_cents'));
        });
    }

    public function test_a_reserved_or_taken_slug_is_refused(): void
    {
        // Arrange
        [$user, $main] = $this->individualOwnerWithSettings();
        Passport::actingAs($user);

        // Act
        $reserved = $this->postJson('/api/organization/units', $this->unitPayload('painel', 'Painel'));
        $taken = $this->postJson('/api/organization/units', $this->unitPayload($main->slug, 'Mesmo slug'));

        // Assert
        $reserved->assertStatus(409)->assertJsonPath('code', 'SLUG_UNAVAILABLE');
        $taken->assertStatus(409)->assertJsonPath('code', 'SLUG_UNAVAILABLE');
    }

    public function test_units_of_the_same_organization_stay_isolated_after_creation(): void
    {
        // Arrange
        [$user, $main] = $this->individualOwnerWithSettings();
        Passport::actingAs($user);
        $branchId = (int) $this->postJson('/api/organization/units', $this->unitPayload('filial-iso', 'Filial Iso'))->json('id');
        app(CurrentTenant::class)->run($main->id, function (): void {
            Product::query()->create(['name' => 'Só na matriz', 'price_in_cents' => 1000, 'is_active' => true, 'position' => 0]);
        });

        // Act
        $onBranch = $this->getJson('/api/products', ['X-Unit' => (string) $branchId]);

        // Assert
        $onBranch->assertOk();
        $this->assertSame([], $onBranch->json());
    }

    public function test_show_organization_lists_units(): void
    {
        // Arrange
        [$user] = $this->individualOwnerWithSettings();
        Passport::actingAs($user);
        $this->postJson('/api/organization/units', $this->unitPayload('filial-lista', 'Filial Lista'))->assertCreated();

        // Act
        $response = $this->getJson('/api/organization');

        // Assert
        $response->assertOk();
        $response->assertJsonPath('type', 'multi_unit');
        $this->assertCount(2, $response->json('units'));
    }

    /** @return array{0: User, 1: Tenant} */
    private function individualOwnerWithSettings(): array
    {
        $organization = Organization::factory()->create(['slug' => 'marca-demo', 'name' => 'Marca Demo']);
        $main = Tenant::factory()->create([
            'organization_id' => $organization->id,
            'is_main' => true,
            'slug' => 'marca-demo',
            'name' => 'Marca Demo',
        ]);
        $user = User::factory()->create([
            'tenant_id' => $main->id,
            'organization_id' => $organization->id,
        ]);
        $organization->update(['owner_user_id' => $user->id]);
        app(CurrentTenant::class)->run($main->id, fn () => app(CreateDefaultSettings::class)->handle(new DefaultSettingsData(name: 'Marca Demo')));

        return [$user, $main];
    }

    /** @return array<string, mixed> */
    private function unitPayload(string $slug, string $name, bool $copySettings = false): array
    {
        return [
            'name' => $name,
            'slug' => $slug,
            'copy_settings' => $copySettings,
            'address' => [
                'zip_code' => '01304001',
                'street' => 'Rua Augusta',
                'number' => '100',
                'complement' => null,
                'district' => 'Consolação',
                'city' => 'São Paulo',
                'state' => 'SP',
                'latitude' => -23.5578,
                'longitude' => -46.6609,
            ],
        ];
    }
}
