<?php

declare(strict_types=1);

namespace Domain\Orchestrator\ClientAppointments\Tests\Feature;

use Domain\Client\Models\Client;
use Domain\Schedule\Enums\BookingStatus;
use Domain\Schedule\Tests\Concerns\ScheduleFixtures;
use Domain\Settings\Models\ProfileSettings;
use Domain\Tenant\Context\CurrentTenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

/** The client area: one account, the appointments of every barbershop where the client booked, and only those. */
final class ClientAppointmentsTest extends TestCase
{
    use RefreshDatabase;
    use ScheduleFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->freezeMondayMorning();
    }

    public function test_lists_the_client_appointments_of_every_barbershop_newest_first_with_the_barbershop(): void
    {
        // Arrange
        $rafael = $this->barbershop();
        $navalha = $this->barbershop('navalha');
        app(CurrentTenant::class)->run($navalha->tenant_id, fn () => ProfileSettings::query()->firstOrFail()->update(['name' => 'Navalha', 'whatsapp_number' => '11999998888']));
        $ana = Client::factory()->registered()->create();
        $other = Client::factory()->create();
        $this->bookingRequest($rafael, '2026-10-12', '10:00', BookingStatus::Completed, attributes: ['client_id' => $ana->id]);
        $this->bookingRequest($navalha, '2026-10-21', '15:00', BookingStatus::Confirmed, 'Barba', ['client_id' => $ana->id]);
        $this->bookingRequest($rafael, '2026-10-22', '09:00', attributes: ['client_id' => $other->id]);
        Passport::actingAs($ana, [], 'client');

        // Act
        $response = $this->getJson('/api/client/appointments');

        // Assert
        $response->assertOk()->assertJsonCount(2)
            ->assertJsonPath('0.service_name', 'Barba')
            ->assertJsonPath('0.date', '2026-10-21')
            ->assertJsonPath('0.status', 'confirmed')
            ->assertJsonPath('0.barbershop.name', 'Navalha')
            ->assertJsonPath('0.barbershop.slug', 'navalha')
            ->assertJsonPath('0.barbershop.whatsapp_url', 'https://wa.me/5511999998888')
            ->assertJsonPath('0.barbershop.booking_url', 'http://localhost:5173/navalha')
            ->assertJsonPath('1.barbershop.slug', 'rafael')
            ->assertJsonPath('1.barbershop.address', 'Rua Augusta, 1500 – Consolação, São Paulo/SP')
            ->assertJsonPath('1.status', 'completed');
        $this->assertArrayNotHasKey('client_whatsapp', $response->json('0'));
    }

    public function test_a_barber_token_cannot_open_the_client_area_and_a_guest_neither(): void
    {
        // Arrange
        $owner = $this->barbershop();

        // Act
        $guest = $this->getJson('/api/client/appointments');
        $this->actingAsOwner($owner);
        $barber = $this->getJson('/api/client/appointments');

        // Assert
        $guest->assertUnauthorized();
        $barber->assertUnauthorized();
    }
}
