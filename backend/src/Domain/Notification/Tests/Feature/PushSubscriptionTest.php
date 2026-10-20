<?php

declare(strict_types=1);

namespace Domain\Notification\Tests\Feature;

use Domain\Identity\Models\User;
use Domain\Notification\Models\PushSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

final class PushSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    private const ENDPOINT = 'https://fcm.googleapis.com/fcm/send/device-1';

    /** @return array<string, mixed> */
    private function subscription(string $endpoint = self::ENDPOINT): array
    {
        return ['endpoint' => $endpoint, 'keys' => ['p256dh' => 'BPublicKeyOfTheBrowser', 'auth' => 'AuthSecret']];
    }

    private function actingAsBarber(): User
    {
        $this->app['auth']->forgetGuards();
        $user = User::factory()->create();
        Passport::actingAs($user);

        return $user;
    }

    public function test_gives_the_public_key_the_browser_needs(): void
    {
        // Arrange
        config(['services.webpush.public_key' => 'BServerPublicKey']);
        $this->actingAsBarber();

        // Act
        $response = $this->getJson('/api/notifications/push/public-key');

        // Assert
        $response->assertOk()->assertExactJson(['public_key' => 'BServerPublicKey']);
    }

    public function test_saves_this_device_for_the_signed_in_barber(): void
    {
        // Arrange
        $barber = $this->actingAsBarber();

        // Act
        $response = $this->postJson('/api/notifications/push/subscriptions', $this->subscription());

        // Assert
        $response->assertNoContent();
        $saved = PushSubscription::withoutTenantScope()->sole();
        $this->assertSame([$barber->tenant_id, $barber->id, 'BPublicKeyOfTheBrowser', 'AuthSecret'], [$saved->tenant_id, $saved->user_id, $saved->public_key, $saved->auth_token]);
    }

    public function test_a_browser_used_by_another_barber_moves_to_the_new_one(): void
    {
        // Arrange
        $this->actingAsBarber();
        $this->postJson('/api/notifications/push/subscriptions', $this->subscription())->assertNoContent();
        $next = $this->actingAsBarber();

        // Act
        $this->postJson('/api/notifications/push/subscriptions', $this->subscription())->assertNoContent();

        // Assert
        $saved = PushSubscription::withoutTenantScope()->sole();
        $this->assertSame([$next->tenant_id, $next->id], [$saved->tenant_id, $saved->user_id]);
    }

    public function test_validates_the_subscription(): void
    {
        // Arrange
        $this->actingAsBarber();

        // Act
        $response = $this->postJson('/api/notifications/push/subscriptions', ['endpoint' => 'http://insecure.test/push', 'keys' => ['p256dh' => '']]);

        // Assert
        $response->assertUnprocessable();
        $errors = $response->json('errors');
        $this->assertSame(['starts_with'], $errors['endpoint']);
        $this->assertSame(['required'], $errors['keys.p256dh']);
        $this->assertSame(['required'], $errors['keys.auth']);
    }

    public function test_a_barber_turns_off_only_an_own_device(): void
    {
        // Arrange
        $this->actingAsBarber();
        $this->postJson('/api/notifications/push/subscriptions', $this->subscription())->assertNoContent();
        $this->actingAsBarber();
        $this->postJson('/api/notifications/push/subscriptions', $this->subscription('https://fcm.googleapis.com/fcm/send/device-2'));

        // Act
        $foreign = $this->deleteJson('/api/notifications/push/subscriptions', ['endpoint' => self::ENDPOINT]);
        $own = $this->deleteJson('/api/notifications/push/subscriptions', ['endpoint' => 'https://fcm.googleapis.com/fcm/send/device-2']);

        // Assert
        $foreign->assertNoContent();
        $own->assertNoContent();
        $this->assertSame([self::ENDPOINT], PushSubscription::withoutTenantScope()->pluck('endpoint')->all());
    }

    public function test_push_settings_require_a_session(): void
    {
        // Act
        $response = $this->postJson('/api/notifications/push/subscriptions', $this->subscription());

        // Assert
        $response->assertUnauthorized();
    }
}
