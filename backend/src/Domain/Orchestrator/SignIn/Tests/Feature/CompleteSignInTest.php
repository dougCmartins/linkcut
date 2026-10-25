<?php

declare(strict_types=1);

namespace Domain\Orchestrator\SignIn\Tests\Feature;

use Domain\Identity\Models\RefreshToken;
use Domain\Identity\Models\User;
use Domain\Identity\Tests\Concerns\SignsIn;
use Domain\Tenant\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

final class CompleteSignInTest extends TestCase
{
    use RefreshDatabase;
    use SignsIn;

    protected function setUp(): void
    {
        parent::setUp();
        $this->preparePassport();
        Notification::fake();
    }

    /** @return array{0: User, 1: string, 2: string} user, challenge id and the code sent */
    private function challengeWithCode(?User $user = null): array
    {
        $user ??= User::factory()->create();
        $challengeId = $this->startLogin($user);
        $this->sendCode($challengeId)->assertCreated();

        return [$user, $challengeId, $this->lastCodeSentTo($user)];
    }

    private function verify(string $challengeId, string $code): TestResponse
    {
        return $this->postJson('/api/two-factor/verify', ['challenge_id' => $challengeId, 'code' => $code]);
    }

    public function test_the_right_code_opens_a_session(): void
    {
        // Arrange
        [$user, $challengeId, $code] = $this->challengeWithCode();

        // Act
        $response = $this->verify($challengeId, $code);

        // Assert
        $response->assertCreated();
        $response->assertJsonPath('token_type', 'Bearer');
        $response->assertJsonPath('expires_in', 900);
        $response->assertJsonPath('refresh_expires_in', 604800);
        $response->assertJsonPath('user.id', $user->id);
        $response->assertJsonPath('user.tenant_id', $user->tenant_id);
        $response->assertJsonMissingPath('code');
        $this->assertSame(1, RefreshToken::query()->where('user_id', $user->id)->count());
        $this->withToken($response->json('access_token'))->getJson('/api/me')->assertOk()->assertJsonPath('email', $user->email);
    }

    public function test_a_wrong_code_tells_how_many_attempts_are_left(): void
    {
        // Arrange
        [, $challengeId, $code] = $this->challengeWithCode();
        $wrongCode = $code === '000000' ? '111111' : '000000';

        // Act
        $response = $this->verify($challengeId, $wrongCode);

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonPath('code', 'INVALID_CODE');
        $response->assertJsonPath('data.attempts_left', 4);
    }

    public function test_the_challenge_locks_after_five_wrong_codes(): void
    {
        // Arrange
        [, $challengeId, $code] = $this->challengeWithCode();
        $wrongCode = $code === '000000' ? '111111' : '000000';
        foreach (range(1, 4) as $attempt) {
            $this->verify($challengeId, $wrongCode)->assertJsonPath('code', 'INVALID_CODE');
        }

        // Act
        $fifth = $this->verify($challengeId, $wrongCode);
        $rightCodeAfterLock = $this->verify($challengeId, $code);

        // Assert
        $fifth->assertStatus(429)->assertJsonPath('code', 'TOO_MANY_ATTEMPTS');
        $rightCodeAfterLock->assertStatus(429)->assertJsonPath('code', 'TOO_MANY_ATTEMPTS');
    }

    public function test_an_expired_code_is_refused(): void
    {
        // Arrange
        [, $challengeId, $code] = $this->challengeWithCode();
        $this->travel(6)->minutes();

        // Act
        $response = $this->verify($challengeId, $code);

        // Assert
        $response->assertStatus(410);
        $response->assertJsonPath('code', 'CODE_EXPIRED');
    }

    public function test_a_code_works_only_once(): void
    {
        // Arrange
        [, $challengeId, $code] = $this->challengeWithCode();
        $this->verify($challengeId, $code)->assertCreated();

        // Act
        $response = $this->verify($challengeId, $code);

        // Assert
        $response->assertStatus(410);
        $response->assertJsonPath('code', 'CHALLENGE_EXPIRED');
    }

    public function test_a_deactivated_tenant_cannot_even_start_signing_in(): void
    {
        // Arrange
        $user = User::factory()->create(['tenant_id' => Tenant::factory()->inactive()]);

        // Act
        $response = $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password', 'slug' => $this->slugOf($user)]);

        // Assert
        $response->assertUnauthorized();
        $response->assertJsonPath('code', 'INVALID_CREDENTIALS');
    }

    public function test_a_tenant_deactivated_during_the_sign_in_cannot_finish_it(): void
    {
        // Arrange
        $user = User::factory()->create();
        [, $challengeId, $code] = $this->challengeWithCode($user);
        Tenant::query()->whereKey($user->tenant_id)->update(['active' => false]);

        // Act
        $response = $this->verify($challengeId, $code);

        // Assert
        $response->assertForbidden();
        $response->assertJsonPath('code', 'TENANT_INACTIVE');
        $this->assertSame(0, RefreshToken::query()->count());
    }

    public function test_invalid_code_format_is_a_validation_error(): void
    {
        // Arrange
        [, $challengeId] = $this->challengeWithCode();

        // Act
        $response = $this->verify($challengeId, '12ab');

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonPath('errors.code.0', 'regex');
    }
}
