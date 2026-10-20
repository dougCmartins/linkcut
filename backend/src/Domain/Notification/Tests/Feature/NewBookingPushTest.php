<?php

declare(strict_types=1);

namespace Domain\Notification\Tests\Feature;

use Domain\Notification\Data\PushMessageData;
use Domain\Notification\Data\TenantPushData;
use Domain\Notification\Jobs\SendPushToTenant;
use Domain\Notification\Models\PushSubscription;
use Domain\Notification\Support\PushSender;
use Domain\Schedule\Tests\Concerns\ScheduleFixtures;
use Domain\Tenant\Context\CurrentTenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

final class NewBookingPushTest extends TestCase
{
    use RefreshDatabase;
    use ScheduleFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->freezeMondayMorning();
    }

    private function device(int $tenantId, int $userId, string $endpoint): void
    {
        app(CurrentTenant::class)->run($tenantId, fn () => PushSubscription::query()->create([
            'user_id' => $userId, 'endpoint' => $endpoint, 'public_key' => 'B', 'auth_token' => 'A',
        ]));
    }

    /** Records what would be sent; endpoints ending in "/gone" act like a device that unsubscribed. */
    private function fakeSender(): object
    {
        $sender = new class implements PushSender
        {
            /** @var list<array{endpoints: list<string>, message: PushMessageData}> */
            public array $sent = [];

            public function send(iterable $subscriptions, PushMessageData $message): array
            {
                $endpoints = collect($subscriptions)->pluck('endpoint')->all();
                $this->sent[] = ['endpoints' => $endpoints, 'message' => $message];

                return array_values(array_filter($endpoints, fn (string $endpoint): bool => str_ends_with($endpoint, '/gone')));
            }
        };
        $this->app->instance(PushSender::class, $sender);

        return $sender;
    }

    public function test_a_new_booking_queues_a_push_for_the_barbershop(): void
    {
        // Arrange
        Queue::fake();
        $owner = $this->barbershop();

        // Act
        $this->postJson('/api/public/rafael/booking-requests', [
            'client_name' => 'Ana Souza', 'client_whatsapp' => '11912345678', 'client_email' => 'joao@example.com', 'service_id' => $this->serviceId($owner, 'Corte'),
            'date' => '2026-10-20', 'time' => '10:00',
        ])->assertCreated();

        // Assert
        Queue::assertPushed(SendPushToTenant::class, function (SendPushToTenant $job) use ($owner): bool {
            return $job->data->tenant_id === $owner->tenant_id
                && $job->data->message->title === 'Novo pedido de agendamento'
                && $job->data->message->body === 'Ana Souza · Corte · 20/10 às 10:00'
                && $job->data->message->url === '/painel';
        });
    }

    public function test_a_refused_booking_sends_nothing(): void
    {
        // Arrange
        Queue::fake();
        $owner = $this->barbershop();

        // Act
        $this->postJson('/api/public/rafael/booking-requests', [
            'client_name' => 'Ana Souza', 'client_whatsapp' => '11912345678', 'client_email' => 'joao@example.com', 'service_id' => $this->serviceId($owner, 'Corte'),
            'date' => '2026-10-25', 'time' => '10:00',
        ])->assertStatus(409);

        // Assert
        Queue::assertNothingPushed();
    }

    public function test_the_push_goes_only_to_devices_of_that_barbershop_and_forgets_gone_ones(): void
    {
        // Arrange
        $sender = $this->fakeSender();
        $owner = $this->barbershop();
        $other = $this->barbershop('navalha');
        $this->device($owner->tenant_id, $owner->id, 'https://push.test/phone');
        $this->device($owner->tenant_id, $owner->id, 'https://push.test/gone');
        $this->device($other->tenant_id, $other->id, 'https://push.test/other-barbershop');
        $message = new PushMessageData(title: 'Novo pedido', body: 'Ana', url: '/painel', tag: 'booking-request-1');

        // Act
        (new SendPushToTenant(new TenantPushData(tenant_id: $owner->tenant_id, message: $message)))->handle(app(CurrentTenant::class), $sender);

        // Assert
        $this->assertSame([['https://push.test/phone', 'https://push.test/gone']], array_column($sender->sent, 'endpoints'));
        $this->assertSame(['https://push.test/other-barbershop', 'https://push.test/phone'], PushSubscription::withoutTenantScope()->orderBy('endpoint')->pluck('endpoint')->all());
    }

    public function test_a_barbershop_without_devices_sends_nothing(): void
    {
        // Arrange
        $sender = $this->fakeSender();
        $owner = $this->barbershop();
        $message = new PushMessageData(title: 'Novo pedido', body: 'Ana', url: '/painel', tag: 'booking-request-1');

        // Act
        (new SendPushToTenant(new TenantPushData(tenant_id: $owner->tenant_id, message: $message)))->handle(app(CurrentTenant::class), $sender);

        // Assert
        $this->assertSame([], $sender->sent);
    }
}
