<?php

declare(strict_types=1);

namespace Tests\Feature;

use Closure;
use Domain\Client\Models\Client;
use Domain\Finance\Models\RevenueEntry;
use Domain\Identity\Models\User;
use Domain\Notification\Jobs\SendPushToClient;
use Domain\Schedule\Enums\BookingStatus;
use Domain\Schedule\Tests\Concerns\ScheduleFixtures;
use Domain\Settings\Actions\CreateDefaultSettings;
use Domain\Settings\Data\DefaultSettingsData;
use Domain\Settings\Models\ProfileSettings;
use Domain\Tenant\Context\CurrentTenant;
use Domain\Tenant\Enums\OrganizationType;
use Domain\Tenant\Models\Organization;
use Domain\Tenant\Models\Tenant;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
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
        // Arrange: the client pushes run later in the queue worker, not in this command.
        Queue::fake([SendPushToClient::class]);
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
            'service_ids' => [$this->serviceId($owner, 'Corte')],
            'date' => '2026-10-20',
            'time' => '10:00',
        ];

        // Act
        $queries = $this->countQueries(fn () => $this->postJson('/api/public/rafael/booking-requests', $payload)->assertCreated());

        // Assert
        // Staff-scoped booking (P4): settings, eligible staff, hours/blocks, lock, client, booking, targeted push users.
        $this->assertLessThanOrEqual(24, $queries, "a booking took {$queries} queries");
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

    public function test_the_brand_page_takes_the_same_queries_for_two_or_five_units(): void
    {
        // Arrange
        $this->brandWithUnits(2, 'marca-duas');
        $forTwo = $this->countQueries(fn () => $this->getJson('/api/public/brand/marca-duas')->assertOk()->assertJsonCount(2, 'units'));
        $this->brandWithUnits(5, 'marca-cinco');

        // Act
        $forFive = $this->countQueries(fn () => $this->getJson('/api/public/brand/marca-cinco')->assertOk()->assertJsonCount(5, 'units'));

        // Assert
        $this->assertSame($forTwo, $forFive, "2 units: {$forTwo} queries; 5 units: {$forFive}");
    }

    public function test_the_brand_report_takes_the_same_queries_for_two_or_six_units(): void
    {
        // Arrange
        $two = $this->brandOwnerWithUnits(2, 'marca-duas');
        $six = $this->brandOwnerWithUnits(6, 'marca-seis');
        $url = '/api/finance/brand/report?from=2026-10-01&to=2026-10-31';

        // Act
        $this->asOwner($two);
        $forTwo = $this->countQueries(fn () => $this->getJson($url)->assertOk()->assertJsonCount(2, 'units'));
        $this->asOwner($six);
        $forSix = $this->countQueries(fn () => $this->getJson($url)->assertOk()->assertJsonCount(6, 'units'));

        // Assert
        $this->assertSame($forTwo, $forSix, "2 units: {$forTwo} queries; 6 units: {$forSix}");
    }

    private function brandWithUnits(int $unitCount, string $orgSlug): void
    {
        $organization = Organization::factory()->create([
            'slug' => $orgSlug,
            'name' => $orgSlug,
            'type' => OrganizationType::MultiUnit,
        ]);
        for ($index = 0; $index < $unitCount; $index++) {
            $slug = $index === 0 ? $orgSlug : "{$orgSlug}-{$index}";
            $tenant = Tenant::factory()->create([
                'organization_id' => $organization->id,
                'is_main' => $index === 0,
                'slug' => $slug,
                'name' => $slug,
                'active' => true,
            ]);
            app(CurrentTenant::class)->run($tenant->id, function () use ($slug): void {
                app(CreateDefaultSettings::class)->handle(new DefaultSettingsData(name: $slug));
                ProfileSettings::query()->firstOrFail()->update([
                    'address_zip_code' => '01304001',
                    'address_street' => 'Rua Augusta',
                    'address_number' => (string) (1000 + strlen($slug)),
                    'address_district' => 'Consolação',
                    'address_city' => 'São Paulo',
                    'address_state' => 'SP',
                ]);
            });
        }
    }

    /** A brand owner whose organization has `$unitCount` units, each with one concluded sale. */
    private function brandOwnerWithUnits(int $unitCount, string $slug): User
    {
        $owner = $this->barbershop($slug);
        $organizationId = (int) $owner->organization_id;
        for ($index = 0; $index < $unitCount; $index++) {
            $unitOwner = $index === 0 ? $owner : $this->barbershop("{$slug}-{$index}");
            Tenant::query()->whereKey($unitOwner->tenant_id)->update(['organization_id' => $organizationId, 'is_main' => $index === 0]);
            app(CurrentTenant::class)->run($unitOwner->tenant_id, fn () => RevenueEntry::query()->create([
                'date' => '2026-10-05', 'time' => '10:00', 'client_name' => 'Ana', 'service_name' => 'Corte',
                'price_in_cents' => 5000, 'cost_in_cents' => 600,
            ]));
        }

        return $owner;
    }

    private function asOwner(User $owner): void
    {
        $this->app['auth']->forgetGuards();
        Passport::actingAs($owner);
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
