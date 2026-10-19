<?php

declare(strict_types=1);

namespace Domain\Tenant\Tests\Feature;

use Domain\Identity\Models\User;
use Domain\Tenant\Events\TenantResolved;
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
}
