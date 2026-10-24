<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Observability\Heartbeat;
use Domain\Client\Models\Client;
use Domain\Client\Models\ClientEmailCode;
use Domain\Identity\Models\SignUpCode;
use Domain\Schedule\Tests\Concerns\ScheduleFixtures;
use Domain\Tenant\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Housekeeping, the heartbeat for the uptime monitor and the minimal admin commands. */
final class OperationsTest extends TestCase
{
    use RefreshDatabase;
    use ScheduleFixtures;

    public function test_the_daily_prune_deletes_only_what_expired_more_than_a_day_ago(): void
    {
        // Arrange
        SignUpCode::query()->create(['email' => 'old@example.com', 'code_hash' => 'x', 'attempts' => 0, 'expires_at' => now()->subDays(2), 'sent_at' => now()->subDays(2)]);
        SignUpCode::query()->create(['email' => 'new@example.com', 'code_hash' => 'x', 'attempts' => 0, 'expires_at' => now()->addMinutes(10), 'sent_at' => now()]);
        $client = Client::factory()->create();
        ClientEmailCode::query()->create(['client_id' => $client->id, 'purpose' => 'password', 'code_hash' => 'x', 'attempts' => 0, 'expires_at' => now()->subDays(3), 'sent_at' => now()->subDays(3)]);

        // Act
        $this->artisan('model:prune', ['--model' => [SignUpCode::class, ClientEmailCode::class]])->assertSuccessful();

        // Assert
        $this->assertSame(['new@example.com'], SignUpCode::query()->pluck('email')->all());
        $this->assertSame(0, ClientEmailCode::query()->count());
    }

    public function test_the_heartbeat_pings_the_monitor_only_when_configured(): void
    {
        // Arrange
        Http::fake();

        // Act
        config(['services.heartbeat.url' => '']);
        (new Heartbeat)->handle();
        config(['services.heartbeat.url' => 'https://hc-ping.com/abc']);
        (new Heartbeat)->handle();

        // Assert
        Http::assertSentCount(1);
        Http::assertSent(fn ($request): bool => $request->url() === 'https://hc-ping.com/abc');
    }

    public function test_a_deactivated_barbershop_disappears_and_comes_back(): void
    {
        // Arrange
        $this->barbershop();

        // Act
        $this->artisan('tenant:deactivate rafael')->assertSuccessful();
        $off = $this->getJson('/api/public/rafael');
        $this->artisan('tenant:activate rafael')->assertSuccessful();
        $on = $this->getJson('/api/public/rafael');

        // Assert
        $off->assertNotFound();
        $on->assertOk();
        $this->assertTrue(Tenant::query()->where('slug', 'rafael')->value('active'));
        $this->artisan('tenant:list')->expectsOutputToContain('rafael')->assertSuccessful();
    }
}
