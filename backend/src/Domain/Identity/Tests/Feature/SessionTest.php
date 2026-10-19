<?php

declare(strict_types=1);

namespace Domain\Identity\Tests\Feature;

use Domain\Identity\Models\RefreshToken;
use Domain\Identity\Models\User;
use Domain\Identity\Tests\Concerns\SignsIn;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Refresh rotation, reuse detection, logout, /me and the default 2FA channel. */
final class SessionTest extends TestCase
{
    use RefreshDatabase;
    use SignsIn;

    protected function setUp(): void
    {
        parent::setUp();
        $this->preparePassport();
    }

    public function test_refresh_rotates_the_refresh_token(): void
    {
        // Arrange
        $session = $this->signIn(User::factory()->create());

        // Act
        $response = $this->postJson('/api/token/refresh', ['refresh_token' => $session['refresh_token']]);

        // Assert
        $response->assertCreated();
        $this->assertNotSame($session['refresh_token'], $response->json('refresh_token'));
        $this->assertNotSame($session['access_token'], $response->json('access_token'));
    }

    public function test_reusing_a_rotated_token_revokes_the_whole_family(): void
    {
        // Arrange
        $session = $this->signIn(User::factory()->create());
        $rotated = $this->postJson('/api/token/refresh', ['refresh_token' => $session['refresh_token']])->json();

        // Act
        $reuse = $this->postJson('/api/token/refresh', ['refresh_token' => $session['refresh_token']]);
        $newestAfterReuse = $this->postJson('/api/token/refresh', ['refresh_token' => $rotated['refresh_token']]);

        // Assert
        $reuse->assertUnauthorized()->assertJsonPath('code', 'INVALID_REFRESH_TOKEN');
        $newestAfterReuse->assertUnauthorized()->assertJsonPath('code', 'INVALID_REFRESH_TOKEN');
        $this->assertSame(0, RefreshToken::query()->whereNull('revoked_at')->count());
    }

    public function test_an_expired_refresh_token_is_refused(): void
    {
        // Arrange
        $session = $this->signIn(User::factory()->create());
        $this->travel(8)->days();

        // Act
        $response = $this->postJson('/api/token/refresh', ['refresh_token' => $session['refresh_token']]);

        // Assert
        $response->assertUnauthorized();
        $response->assertJsonPath('code', 'INVALID_REFRESH_TOKEN');
    }

    public function test_logout_revokes_the_access_and_refresh_tokens(): void
    {
        // Arrange
        $session = $this->signIn(User::factory()->create());

        // Act
        $response = $this->withToken($session['access_token'])
            ->postJson('/api/logout', ['refresh_token' => $session['refresh_token']]);

        // Assert
        $response->assertNoContent();
        $this->app['auth']->forgetGuards();
        $this->withToken($session['access_token'])->getJson('/api/me')->assertUnauthorized()->assertJsonPath('code', 'UNAUTHENTICATED');
        $this->postJson('/api/token/refresh', ['refresh_token' => $session['refresh_token']])->assertUnauthorized();
    }

    public function test_me_requires_a_token(): void
    {
        // Act
        $response = $this->getJson('/api/me');

        // Assert
        $response->assertUnauthorized();
        $response->assertJsonPath('code', 'UNAUTHENTICATED');
    }

    public function test_the_default_two_factor_channel_can_be_changed_and_cleared(): void
    {
        // Arrange
        $session = $this->signIn(User::factory()->create(['phone' => '+5511988887777']));

        // Act
        $toSms = $this->withToken($session['access_token'])->patchJson('/api/me/two-factor-channel', ['channel' => 'sms']);
        $cleared = $this->withToken($session['access_token'])->patchJson('/api/me/two-factor-channel', ['channel' => null]);

        // Assert
        $toSms->assertOk()->assertJsonPath('two_factor_channel', 'sms');
        $cleared->assertOk()->assertJsonPath('two_factor_channel', null);
    }

    public function test_sms_cannot_be_the_default_without_a_phone(): void
    {
        // Arrange
        $session = $this->signIn(User::factory()->withoutPhone()->create());

        // Act
        $response = $this->withToken($session['access_token'])->patchJson('/api/me/two-factor-channel', ['channel' => 'sms']);

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonPath('code', 'CHANNEL_UNAVAILABLE');
    }
}
