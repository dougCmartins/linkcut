<?php

declare(strict_types=1);

namespace Domain\Tenant\Tests\Feature;

use Domain\Identity\Models\User;
use Domain\Tenant\Enums\OrganizationType;
use Domain\Tenant\Events\TenantResolved;
use Domain\Tenant\Models\Organization;
use Domain\Tenant\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Passport\Passport;
use Tests\TestCase;

final class ResolveTenantTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_authenticated_request_resolves_the_tenant_of_the_user(): void
    {
        // Arrange
        Event::fake([TenantResolved::class]);
        $user = User::factory()->create();
        Passport::actingAs($user);

        // Act
        $response = $this->getJson('/api/me');

        // Assert
        $response->assertOk();
        Event::assertDispatched(TenantResolved::class, fn (TenantResolved $event) => $event->tenantId === $user->tenant_id);
    }

    public function test_a_deactivated_tenant_is_refused_with_the_error_envelope(): void
    {
        // Arrange
        $user = User::factory()->create(['tenant_id' => Tenant::factory()->inactive()]);
        Passport::actingAs($user);

        // Act
        $response = $this->getJson('/api/me');

        // Assert
        $response->assertForbidden();
        $response->assertJsonPath('code', 'TENANT_INACTIVE');
        $response->assertJsonPath('data', null);
    }

    public function test_without_x_unit_the_main_unit_is_resolved(): void
    {
        // Arrange
        Event::fake([TenantResolved::class]);
        [$user, $main, $branch] = $this->multiUnitOwner();
        Passport::actingAs($user);

        // Act
        $response = $this->getJson('/api/me');

        // Assert
        $response->assertOk();
        Event::assertDispatched(TenantResolved::class, fn (TenantResolved $event) => $event->tenantId === $main->id);
        $this->assertNotSame($branch->id, $main->id);
    }

    public function test_x_unit_selects_a_unit_of_the_same_organization(): void
    {
        // Arrange
        Event::fake([TenantResolved::class]);
        [$user, $main, $branch] = $this->multiUnitOwner();
        Passport::actingAs($user);

        // Act
        $response = $this->getJson('/api/me', ['X-Unit' => (string) $branch->id]);

        // Assert
        $response->assertOk();
        Event::assertDispatched(TenantResolved::class, fn (TenantResolved $event) => $event->tenantId === $branch->id);
        $this->assertSame($main->id, $user->tenant_id);
    }

    public function test_a_unit_from_another_organization_is_forbidden(): void
    {
        // Arrange
        [$user] = $this->multiUnitOwner();
        $foreign = Tenant::factory()->create();
        Passport::actingAs($user);

        // Act
        $response = $this->getJson('/api/me', ['X-Unit' => (string) $foreign->id]);

        // Assert
        $response->assertForbidden();
        $response->assertJsonPath('code', 'UNIT_FORBIDDEN');
        $response->assertJsonPath('data', null);
    }

    /** @return array{0: User, 1: Tenant, 2: Tenant} */
    private function multiUnitOwner(): array
    {
        $organization = Organization::factory()->multiUnit()->create();
        $main = Tenant::factory()->create([
            'organization_id' => $organization->id,
            'is_main' => true,
            'slug' => $organization->slug.'-main',
        ]);
        $branch = Tenant::factory()->create([
            'organization_id' => $organization->id,
            'is_main' => false,
            'slug' => $organization->slug.'-branch',
        ]);
        $user = User::factory()->create([
            'tenant_id' => $main->id,
            'organization_id' => $organization->id,
        ]);
        $organization->update([
            'owner_user_id' => $user->id,
            'type' => OrganizationType::MultiUnit,
        ]);

        return [$user, $main, $branch];
    }
}
