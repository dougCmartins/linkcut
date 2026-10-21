<?php

declare(strict_types=1);

namespace Tests\Feature;

use Closure;
use Domain\Client\Models\Client;
use Domain\Identity\Models\User;
use Domain\Schedule\Enums\BookingStatus;
use Domain\Schedule\Tests\Concerns\ScheduleFixtures;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Laravel\Passport\Passport;
use Tests\TestCase;

/**
 * Guards against N+1: the number of queries of the busiest paths must not grow with the data
 * (more barbershops, more appointments, more clients).
 */
final class QueryBudgetTest extends TestCase
{
    use RefreshDatabase;
    use ScheduleFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->freezeMondayMorning('10:00');
        Notification::fake();
    }

    public function test_client_appointments_take_the_same_queries_for_one_or_five_barbershops(): void
    {
        // Arrange
        $one = $this->clientWithBookingsAt(1, 'ana@example.com');
        $five = $this->clientWithBookingsAt(5, 'bia@example.com');

        // Act
        $forOne = $this->countQueries(fn () => $this->asClient($one)->getJson('/api/client/appointments')->assertOk()->assertJsonCount(1));
        $forFive = $this->countQueries(fn () => $this->asClient($five)->getJson('/api/client/appointments')->assertOk()->assertJsonCount(5));

        // Assert
        $this->assertSame($forOne, $forFive, "1 barbershop: {$forOne} queries; 5 barbershops: {$forFive}");
    }

    public function test_reminders_take_the_same_queries_for_one_or_twenty_appointments(): void
    {
        // Arrange
        $owner = $this->barbershop();
        $this->remindableBookings($owner, 1);
        $forOne = $this->countQueries(fn () => $this->artisan('schedule:send-reminders')->expectsOutput('Reminders sent: 1'));
        $this->remindableBookings($owner, 20);

        // Act
        $forTwenty = $this->countQueries(fn () => $this->artisan('schedule:send-reminders')->expectsOutput('Reminders sent: 20'));

        // Assert
        $this->assertSame($forOne, $forTwenty, "1 reminder: {$forOne} queries; 20 reminders: {$forTwenty}");
    }

    public function test_invitations_take_the_same_queries_for_one_or_twenty_clients(): void
    {
        // Arrange
        Client::factory()->create(['created_at' => now()->subDay()]);
        $forOne = $this->countQueries(fn () => $this->artisan('client:invite-registration')->expectsOutput('Invitations sent: 1'));
        Client::factory()->count(20)->create(['created_at' => now()->subDay()]);

        // Act
        $forTwenty = $this->countQueries(fn () => $this->artisan('client:invite-registration')->expectsOutput('Invitations sent: 20'));

        // Assert
        $this->assertSame($forOne, $forTwenty, "1 invitation: {$forOne} queries; 20 invitations: {$forTwenty}");
    }

    public function test_a_public_booking_stays_within_its_query_budget(): void
    {
        // Arrange
        $owner = $this->barbershop();
        $payload = [
            'client_name' => 'João Silva',
            'client_whatsapp' => '(11) 91234-5678',
            'client_email' => 'joao@example.com',
            'service_id' => $this->serviceId($owner, 'Corte'),
            'date' => '2026-10-20',
            'time' => '10:00',
        ];

        // Act
        $queries = $this->countQueries(fn () => $this->postJson('/api/public/rafael/booking-requests', $payload)->assertCreated());

        // Assert
        $this->assertLessThanOrEqual(15, $queries, "a booking took {$queries} queries");
    }

    public function test_the_slug_check_takes_one_query_even_when_many_links_are_taken(): void
    {
        // Arrange
        foreach (['studio', 'studio-2', 'studio-3', 'studio-4'] as $slug) {
            $this->barbershop($slug);
        }

        // Act
        $queries = $this->countQueries(fn () => $this->getJson('/api/sign-up/slug?slug=studio')
            ->assertOk()->assertJsonPath('available', false)->assertJsonPath('suggestion', 'studio-5'));

        // Assert
        $this->assertSame(1, $queries);
    }

    private function clientWithBookingsAt(int $barbershops, string $email): Client
    {
        $client = Client::factory()->registered()->create(['email' => $email]);
        for ($index = 0; $index < $barbershops; $index++) {
            $owner = $this->barbershop(str_replace(['@', '.'], '-', $email)."-{$index}");
            $this->bookingRequest($owner, '2026-10-21', '10:00', BookingStatus::Confirmed, attributes: ['client_id' => $client->id]);
        }

        return $client;
    }

    private function remindableBookings(User $owner, int $count): void
    {
        for ($index = 0; $index < $count; $index++) {
            $client = Client::factory()->create();
            $this->bookingRequest($owner, '2026-10-20', sprintf('%02d:%02d', 7 + intdiv($index, 4), ($index % 4) * 15), BookingStatus::Confirmed, attributes: [
                'client_email' => $client->email,
                'client_id' => $client->id,
            ]);
        }
    }

    private function asClient(Client $client): static
    {
        $this->app['auth']->forgetGuards();
        Passport::actingAs($client, [], 'client');

        return $this;
    }

    private function countQueries(Closure $callback): int
    {
        $count = 0;
        DB::listen(function () use (&$count): void {
            $count++;
        });
        $callback();
        DB::getEventDispatcher()?->forget(QueryExecuted::class);

        return $count;
    }
}
