<?php

declare(strict_types=1);

namespace Domain\Identity\Tests\Feature;

use App\Notifications\Channels\SmsChannel;
use Domain\Identity\Models\TwoFactorChallenge;
use Domain\Identity\Models\User;
use Domain\Identity\Notifications\TwoFactorCodeNotification;
use Domain\Identity\Tests\Concerns\SignsIn;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

final class SendTwoFactorCodeTest extends TestCase
{
    use RefreshDatabase;
    use SignsIn;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    public function test_sends_the_code_by_email(): void
    {
        // Arrange
        $user = User::factory()->create(['email' => 'rafael@linkcut.app']);
        $challengeId = $this->startLogin($user);

        // Act
        $response = $this->sendCode($challengeId, 'email');

        // Assert
        $response->assertCreated();
        $response->assertJsonPath('masked_destination', 'ra****@linkcut.app');
        Notification::assertSentTo($user, TwoFactorCodeNotification::class, fn ($notification, array $channels) => $channels === ['mail']);
    }

    public function test_sends_the_code_by_sms(): void
    {
        // Arrange
        $user = User::factory()->create(['phone' => '+5511988887777']);
        $challengeId = $this->startLogin($user);

        // Act
        $response = $this->sendCode($challengeId, 'sms');

        // Assert
        $response->assertCreated();
        $response->assertJsonPath('masked_destination', '+55 •• •••••-7777');
        Notification::assertSentTo($user, TwoFactorCodeNotification::class, fn ($notification, array $channels) => $channels === [SmsChannel::class]);
    }

    public function test_the_code_is_stored_only_as_a_hash(): void
    {
        // Arrange
        $user = User::factory()->create();
        $challengeId = $this->startLogin($user);

        // Act
        $this->sendCode($challengeId);

        // Assert
        $code = $this->lastCodeSentTo($user);
        $challenge = TwoFactorChallenge::query()->findOrFail($challengeId);
        $this->assertNotNull($challenge->code_hash);
        $this->assertStringNotContainsString($code, $challenge->code_hash);
    }

    public function test_sms_without_a_phone_is_refused(): void
    {
        // Arrange
        $user = User::factory()->withoutPhone()->create();
        $challengeId = $this->startLogin($user);

        // Act
        $response = $this->sendCode($challengeId, 'sms');

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonPath('code', 'CHANNEL_UNAVAILABLE');
        Notification::assertNothingSent();
    }

    public function test_asking_again_too_soon_is_refused(): void
    {
        // Arrange
        $user = User::factory()->create();
        $challengeId = $this->startLogin($user);
        $this->sendCode($challengeId)->assertCreated();

        // Act
        $response = $this->sendCode($challengeId);

        // Assert
        $response->assertStatus(429);
        $response->assertJsonPath('code', 'RESEND_TOO_SOON');
    }

    public function test_a_new_code_can_be_sent_after_the_wait(): void
    {
        // Arrange
        $user = User::factory()->create();
        $challengeId = $this->startLogin($user);
        $this->sendCode($challengeId)->assertCreated();
        $this->travel(31)->seconds();

        // Act
        $response = $this->sendCode($challengeId);

        // Assert
        $response->assertCreated();
        Notification::assertSentToTimes($user, TwoFactorCodeNotification::class, 2);
    }

    public function test_an_expired_challenge_cannot_send_codes(): void
    {
        // Arrange
        $user = User::factory()->create();
        $challengeId = $this->startLogin($user);
        $this->travel(11)->minutes();

        // Act
        $response = $this->sendCode($challengeId);

        // Assert
        $response->assertStatus(410);
        $response->assertJsonPath('code', 'CHALLENGE_EXPIRED');
    }
}
