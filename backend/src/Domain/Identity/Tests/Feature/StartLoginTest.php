<?php

declare(strict_types=1);

namespace Domain\Identity\Tests\Feature;

use Domain\Identity\Enums\TwoFactorChannel;
use Domain\Identity\Models\TwoFactorChallenge;
use Domain\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StartLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_right_password_opens_a_challenge_with_masked_destinations(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email' => 'rafael@linkcut.app',
            'phone' => '+5511988887777',
            'two_factor_channel' => TwoFactorChannel::Sms,
        ]);

        // Act
        $response = $this->postJson('/api/login', ['email' => 'rafael@linkcut.app', 'password' => 'password']);

        // Assert
        $response->assertCreated();
        $response->assertJsonPath('preferred_channel', 'sms');
        $response->assertJsonPath('destinations.0', ['channel' => 'email', 'masked' => 'ra****@linkcut.app']);
        $response->assertJsonPath('destinations.1', ['channel' => 'sms', 'masked' => '+55 •• •••••-7777']);
        $response->assertJsonMissingPath('access_token');
        $this->assertTrue(TwoFactorChallenge::query()->whereKey($response->json('challenge_id'))->exists());
    }

    public function test_sms_is_not_offered_without_a_phone(): void
    {
        // Arrange
        $user = User::factory()->withoutPhone()->create();

        // Act
        $response = $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password']);

        // Assert
        $response->assertJsonCount(1, 'destinations');
        $response->assertJsonPath('destinations.0.channel', 'email');
    }

    public function test_wrong_password_and_unknown_email_give_the_same_error(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $wrongPassword = $this->postJson('/api/login', ['email' => $user->email, 'password' => 'wrong-password']);
        $unknownEmail = $this->postJson('/api/login', ['email' => 'nobody@linkcut.app', 'password' => 'password']);

        // Assert
        foreach ([$wrongPassword, $unknownEmail] as $response) {
            $response->assertUnauthorized();
            $response->assertJsonPath('code', 'INVALID_CREDENTIALS');
        }
        $this->assertSame(0, TwoFactorChallenge::query()->count());
    }

    public function test_invalid_input_returns_the_failed_rule_per_field(): void
    {
        // Arrange
        $payload = ['email' => 'not-an-email'];

        // Act
        $response = $this->postJson('/api/login', $payload);

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonPath('code', 'VALIDATION_FAILED');
        $response->assertJsonPath('errors.email.0', 'email');
        $response->assertJsonPath('errors.password.0', 'required');
        $this->assertNotEmpty($response->json('request_id'));
    }

    public function test_too_many_attempts_are_throttled(): void
    {
        // Arrange
        $user = User::factory()->create();
        foreach (range(1, 5) as $attempt) {
            $this->postJson('/api/login', ['email' => $user->email, 'password' => 'wrong-'.$attempt]);
        }

        // Act
        $response = $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password']);

        // Assert
        $response->assertStatus(429);
        $response->assertJsonPath('code', 'TOO_MANY_REQUESTS');
    }
}
