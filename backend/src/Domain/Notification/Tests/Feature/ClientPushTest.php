<?php

declare(strict_types=1);

namespace Domain\Notification\Tests\Feature;

use Domain\Client\Models\Client;
use Domain\Notification\Jobs\SendPushToClient;
use Domain\Notification\Models\ClientPushSubscription;
use Domain\Schedule\Enums\BookingStatus;
use Domain\Schedule\Tests\Concerns\ScheduleFixtures;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Laravel\Passport\Passport;
use Tests\TestCase;

/** Clients told on their phone when the barbershop answers (not only by e-mail). */
final class ClientPushTest extends TestCase
{
    use RefreshDatabase;
    use ScheduleFixtures;

    private const SUBSCRIPTION = ['endpoint' => 'https://fcm.googleapis.com/fcm/send/client-1', 'keys' => ['p256dh' => 'BKeyOfTheBrowser', 'auth' => 'Secret']];

    protected function setUp(): void
    {
        parent::setUp();
        $this->freezeMondayMorning();
        Notification::fake();
    }

    /** @return array<string, mixed> */
    private function bookingPayload(int $serviceId): array
    {
        return ['client_name' => 'Ana Souza', 'client_whatsapp' => '11912345678', 'client_email' => 'ana@example.com', 'service_ids' => [$serviceId], 'date' => '2026-10-20', 'time' => '10:00'];
    }

    public function test_right_after_booking_this_device_can_sign_up_with_the_signed_link_of_the_booking(): void
    {
        // Arrange
        $owner = $this->barbershop();
        $pushUrl = $this->postJson('/api/public/rafael/booking-requests', $this->bookingPayload($this->serviceId($owner, 'Corte')))->json('push_url');

        // Act
        $response = $this->postJson($pushUrl, self::SUBSCRIPTION);
        $tampered = $this->postJson(str_replace('client-push/', 'client-push/9', $pushUrl), self::SUBSCRIPTION);

        // Assert
        $response->assertNoContent();
        $tampered->assertForbidden();
        $client = Client::query()->where('email', 'ana@example.com')->firstOrFail();
        $this->assertSame($client->id, ClientPushSubscription::query()->value('client_id'));
    }

    public function test_the_signed_link_expires(): void
    {
        // Arrange
        $owner = $this->barbershop();
        $pushUrl = $this->postJson('/api/public/rafael/booking-requests', $this->bookingPayload($this->serviceId($owner, 'Corte')))->json('push_url');
        $this->travel(3)->hours();

        // Act
        $response = $this->postJson($pushUrl, self::SUBSCRIPTION);

        // Assert
        $response->assertForbidden();
        $this->assertSame(0, ClientPushSubscription::query()->count());
    }

    public function test_a_signed_in_client_signs_up_this_device_and_unknown_push_services_are_refused(): void
    {
        // Arrange
        $client = Client::factory()->registered()->create();
        Passport::actingAs($client, [], 'client');

        // Act
        $saved = $this->postJson('/api/client/push-subscriptions', self::SUBSCRIPTION);
        $foreign = $this->postJson('/api/client/push-subscriptions', ['endpoint' => 'https://evil.example/push', 'keys' => self::SUBSCRIPTION['keys']]);

        // Assert
        $saved->assertNoContent();
        $foreign->assertUnprocessable()->assertJsonPath('errors.endpoint.0', 'url');
        $this->assertSame([$client->id], ClientPushSubscription::query()->pluck('client_id')->all());
    }

    public function test_accepting_a_booking_pushes_to_the_client_who_booked(): void
    {
        // Arrange
        Queue::fake([SendPushToClient::class]);
        $owner = $this->barbershop();
        $client = Client::factory()->create(['email' => 'ana@example.com']);
        $booking = $this->bookingRequest($owner, '2026-10-20', '10:00', BookingStatus::Pending, attributes: ['client_id' => $client->id, 'client_email' => 'ana@example.com']);
        $this->actingAsOwner($owner);

        // Act
        $this->patchJson("/api/schedule/requests/{$booking->id}/accept")->assertOk();

        // Assert
        Queue::assertPushed(SendPushToClient::class, fn (SendPushToClient $job): bool => $job->data->client_id === $client->id
            && $job->data->message->title === 'Horário confirmado ✅'
            && str_contains($job->data->message->body, '20/10 às 10:00')
            && $job->data->message->url === '/conta');
    }
}
