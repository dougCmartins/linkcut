<?php

declare(strict_types=1);

namespace Domain\Identity\Tests\Feature;

use Domain\Identity\Enums\TwoFactorChannel;
use Domain\Identity\Models\User;
use Domain\Identity\Tests\Concerns\SignsIn;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Passport\Passport;
use Tests\TestCase;

/** With SMS off (no budget yet), the code goes by e-mail only, even for users with a phone. */
final class SmsDisabledTest extends TestCase
{
    use RefreshDatabase;
    use SignsIn;

    protected function setUp(): void
    {
        parent::setUp();
        config(['sms.enabled' => false]);
        Notification::fake();
    }

    public function test_login_offers_only_e_mail_and_ignores_a_saved_sms_preference(): void
    {
        // Arrange
        $user = User::factory()->create(['phone' => '+5511988887777', 'two_factor_channel' => TwoFactorChannel::Sms]);

        // Act
        $response = $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password']);

        // Assert
        $response->assertCreated()->assertJsonCount(1, 'destinations')->assertJsonPath('destinations.0.channel', 'email');
        $response->assertJsonPath('preferred_channel', null);
    }

    public function test_sending_by_sms_is_refused(): void
    {
        // Arrange
        $user = User::factory()->create(['phone' => '+5511988887777']);
        $challengeId = $this->startLogin($user);

        // Act
        $response = $this->sendCode($challengeId, 'sms');

        // Assert
        $response->assertUnprocessable()->assertJsonPath('code', 'CHANNEL_UNAVAILABLE');
        Notification::assertNothingSent();
    }

    public function test_sms_cannot_be_the_default_and_me_lists_only_e_mail(): void
    {
        // Arrange
        Passport::actingAs(User::factory()->create(['phone' => '+5511988887777']));

        // Act
        $update = $this->patchJson('/api/me/two-factor-channel', ['channel' => 'sms']);
        $me = $this->getJson('/api/me');

        // Assert
        $update->assertUnprocessable()->assertJsonPath('code', 'CHANNEL_UNAVAILABLE');
        $me->assertOk()->assertJsonPath('available_channels', ['email']);
    }
}
