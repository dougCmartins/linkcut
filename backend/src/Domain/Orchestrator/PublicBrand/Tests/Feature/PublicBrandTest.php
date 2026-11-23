<?php

declare(strict_types=1);

namespace Domain\Orchestrator\PublicBrand\Tests\Feature;

use Domain\Identity\Models\User;
use Domain\Settings\Actions\CreateDefaultSettings;
use Domain\Settings\Data\DefaultSettingsData;
use Domain\Settings\Models\ProfileSettings;
use Domain\Tenant\Context\CurrentTenant;
use Domain\Tenant\Enums\OrganizationType;
use Domain\Tenant\Models\Organization;
use Domain\Tenant\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Passport\Passport;
use Tests\TestCase;

final class PublicBrandTest extends TestCase
{
    use RefreshDatabase;

    public function test_unknown_brand_slug_returns_404_envelope(): void
    {
        // Arrange / Act
        $response = $this->getJson('/api/public/brand/nao-existe');

        // Assert
        $response->assertNotFound();
        $response->assertJsonPath('code', 'BRAND_NOT_FOUND');
    }

    public function test_brand_lists_only_active_units_of_its_organization(): void
    {
        // Arrange
        [$organization, $main, $branch] = $this->multiUnitBrand();
        $other = Organization::factory()->create(['slug' => 'outra-marca', 'name' => 'Outra']);
        Tenant::factory()->create([
            'organization_id' => $other->id,
            'is_main' => true,
            'slug' => 'outra-marca',
            'name' => 'Outra',
            'active' => true,
        ]);
        Tenant::factory()->create([
            'organization_id' => $organization->id,
            'is_main' => false,
            'slug' => 'filial-inativa',
            'name' => 'Inativa',
            'active' => false,
        ]);

        // Act
        $response = $this->getJson("/api/public/brand/{$organization->slug}");

        // Assert
        $response->assertOk();
        $response->assertJsonPath('name', 'Marca Demo');
        $response->assertJsonPath('slug', 'marca-demo');
        $slugs = collect($response->json('units'))->pluck('slug')->all();
        $this->assertSame(['marca-demo', 'filial-centro'], $slugs);
        $this->assertStringContainsString('Rua Augusta', (string) data_get($response->json('units.0.address'), 'street'));
        $this->assertTrue($response->json('units.0.is_main'));
        $this->assertFalse($response->json('units.1.is_main'));
        $this->assertNotContains('outra-marca', $slugs);
        $this->assertNotContains('filial-inativa', $slugs);
        $this->assertSame($main->slug, $response->json('units.0.slug'));
        $this->assertSame($branch->slug, $response->json('units.1.slug'));
    }

    public function test_brand_response_is_cached_and_forgotten_when_a_unit_is_created(): void
    {
        // Arrange
        [$organization, $main] = $this->multiUnitBrand(withBranch: false);
        $user = User::factory()->create([
            'tenant_id' => $main->id,
            'organization_id' => $organization->id,
        ]);
        $organization->update(['owner_user_id' => $user->id]);
        Passport::actingAs($user);
        $this->getJson('/api/public/brand/marca-demo')->assertOk()->assertJsonCount(1, 'units');
        $this->assertTrue(Cache::has("brand:{$organization->id}"));

        // Act
        $this->postJson('/api/organization/units', [
            'name' => 'Filial Nova',
            'slug' => 'filial-nova',
            'copy_settings' => false,
            'address' => [
                'zip_code' => '01304001',
                'street' => 'Rua Augusta',
                'number' => '100',
                'complement' => null,
                'district' => 'Consolação',
                'city' => 'São Paulo',
                'state' => 'SP',
                'latitude' => -23.55,
                'longitude' => -46.66,
            ],
        ])->assertCreated();

        // Assert
        $this->assertFalse(Cache::has("brand:{$organization->id}"));
        $this->getJson('/api/public/brand/marca-demo')->assertOk()->assertJsonCount(2, 'units');
    }

    public function test_public_brand_route_is_throttled(): void
    {
        // Arrange
        $this->multiUnitBrand(withBranch: false);
        RateLimiter::clear('public');

        // Act / Assert: middleware is attached (route uses throttle:public).
        $this->getJson('/api/public/brand/marca-demo')->assertOk();
        $route = app('router')->getRoutes()->match(
            request()->create('/api/public/brand/marca-demo', 'GET')
        );
        $this->assertContains('throttle:public', $route->gatherMiddleware());
    }

    /**
     * @return array{0: Organization, 1: Tenant, 2?: Tenant}
     */
    private function multiUnitBrand(bool $withBranch = true): array
    {
        $organization = Organization::factory()->create([
            'slug' => 'marca-demo',
            'name' => 'Marca Demo',
            'type' => $withBranch ? OrganizationType::MultiUnit : OrganizationType::Individual,
        ]);
        $main = Tenant::factory()->create([
            'organization_id' => $organization->id,
            'is_main' => true,
            'slug' => 'marca-demo',
            'name' => 'Marca Demo',
            'active' => true,
        ]);
        app(CurrentTenant::class)->run($main->id, function (): void {
            app(CreateDefaultSettings::class)->handle(new DefaultSettingsData(name: 'Marca Demo'));
            ProfileSettings::query()->firstOrFail()->update([
                'address_zip_code' => '01304001',
                'address_street' => 'Rua Augusta',
                'address_number' => '1500',
                'address_district' => 'Consolação',
                'address_city' => 'São Paulo',
                'address_state' => 'SP',
                'latitude' => -23.5578,
                'longitude' => -46.6609,
            ]);
        });

        if (! $withBranch) {
            return [$organization, $main];
        }

        $branch = Tenant::factory()->create([
            'organization_id' => $organization->id,
            'is_main' => false,
            'slug' => 'filial-centro',
            'name' => 'Filial Centro',
            'active' => true,
        ]);
        app(CurrentTenant::class)->run($branch->id, function (): void {
            app(CreateDefaultSettings::class)->handle(new DefaultSettingsData(name: 'Filial Centro'));
            ProfileSettings::query()->firstOrFail()->update([
                'address_zip_code' => '01304001',
                'address_street' => 'Av Paulista',
                'address_number' => '1000',
                'address_district' => 'Bela Vista',
                'address_city' => 'São Paulo',
                'address_state' => 'SP',
            ]);
        });

        return [$organization, $main, $branch];
    }
}
