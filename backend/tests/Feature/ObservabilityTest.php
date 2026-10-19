<?php

declare(strict_types=1);

namespace Tests\Feature;

use Domain\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Laravel\Passport\Passport;
use RuntimeException;
use Tests\TestCase;

final class ObservabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_api_response_carries_a_request_id(): void
    {
        // Act
        $response = $this->postJson('/api/login', []);

        // Assert
        $requestId = $response->headers->get('X-Request-Id');
        $this->assertNotEmpty($requestId);
        $response->assertJsonPath('request_id', $requestId);
    }

    public function test_a_valid_request_id_from_the_spa_is_kept(): void
    {
        // Act
        $response = $this->withHeader('X-Request-Id', 'spa-1234-abcd')->postJson('/api/login', []);

        // Assert
        $response->assertHeader('X-Request-Id', 'spa-1234-abcd');
    }

    public function test_unexpected_errors_return_a_generic_500_envelope(): void
    {
        // Arrange
        config(['app.debug' => false]);
        Route::middleware('api')->get('/api/_boom', fn () => throw new RuntimeException('database password leaked here'));

        // Act
        $response = $this->getJson('/api/_boom');

        // Assert
        $response->assertStatus(500);
        $response->assertJsonPath('code', 'SERVER_ERROR');
        $response->assertJsonPath('request_id', $response->headers->get('X-Request-Id'));
        $this->assertStringNotContainsString('password', $response->getContent());
    }

    public function test_unknown_api_routes_use_the_envelope(): void
    {
        // Act
        $response = $this->getJson('/api/does-not-exist');

        // Assert
        $response->assertNotFound();
        $response->assertJsonPath('code', 'NOT_FOUND');
    }

    public function test_logs_of_an_authenticated_request_carry_tenant_and_user(): void
    {
        // Arrange
        $user = User::factory()->create();
        Passport::actingAs($user);

        // Act
        $this->getJson('/api/me')->assertOk();

        // Assert
        $context = Log::sharedContext();
        $this->assertSame($user->tenant_id, $context['tenant_id']);
        $this->assertSame($user->id, $context['user_id']);
        $this->assertNotEmpty($context['request_id']);
    }

    public function test_pulse_asks_for_basic_auth_outside_local(): void
    {
        // Arrange
        config(['pulse.basic_auth.user' => 'ops', 'pulse.basic_auth.password_hash' => Hash::make('secret')]);

        // Act
        $anonymous = $this->get('/pulse');
        $wrongPassword = $this->withHeader('Authorization', 'Basic '.base64_encode('ops:wrong'))->get('/pulse');

        // Assert
        $anonymous->assertUnauthorized();
        $anonymous->assertHeader('WWW-Authenticate', 'Basic realm="Pulse"');
        $wrongPassword->assertUnauthorized();
    }

    public function test_pulse_opens_with_the_right_credentials(): void
    {
        // Arrange
        config(['pulse.basic_auth.user' => 'ops', 'pulse.basic_auth.password_hash' => Hash::make('secret')]);

        // Act
        $response = $this->withHeader('Authorization', 'Basic '.base64_encode('ops:secret'))->get('/pulse');

        // Assert
        $this->assertNotSame(401, $response->getStatusCode());
    }
}
