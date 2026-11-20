<?php

declare(strict_types=1);

namespace Domain\Tenant\Tests\Feature;

use Domain\Catalog\Models\Product;
use Domain\Identity\Models\User;
use Domain\Tenant\Context\CurrentTenant;
use Domain\Tenant\Enums\OrganizationType;
use Domain\Tenant\Models\Organization;
use Domain\Tenant\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

final class UnitIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_data_from_one_unit_does_not_appear_on_another_unit_of_the_same_organization(): void
    {
        // Arrange
        $organization = Organization::factory()->multiUnit()->create();
        $unitA = Tenant::factory()->create(['organization_id' => $organization->id, 'is_main' => true, 'slug' => 'unit-a']);
        $unitB = Tenant::factory()->create(['organization_id' => $organization->id, 'is_main' => false, 'slug' => 'unit-b']);
        $user = User::factory()->create(['tenant_id' => $unitA->id, 'organization_id' => $organization->id]);
        $organization->update(['owner_user_id' => $user->id, 'type' => OrganizationType::MultiUnit]);

        app(CurrentTenant::class)->run($unitA->id, function (): void {
            Product::query()->create(['name' => 'Pomada A', 'price_in_cents' => 1000, 'is_active' => true, 'position' => 0]);
        });
        app(CurrentTenant::class)->run($unitB->id, function (): void {
            Product::query()->create(['name' => 'Pomada B', 'price_in_cents' => 2000, 'is_active' => true, 'position' => 0]);
        });
        Passport::actingAs($user);

        // Act
        $onA = $this->getJson('/api/products', ['X-Unit' => (string) $unitA->id]);
        $onB = $this->getJson('/api/products', ['X-Unit' => (string) $unitB->id]);

        // Assert
        $onA->assertOk();
        $onB->assertOk();
        $this->assertSame(['Pomada A'], collect($onA->json())->pluck('name')->all());
        $this->assertSame(['Pomada B'], collect($onB->json())->pluck('name')->all());
    }
}
