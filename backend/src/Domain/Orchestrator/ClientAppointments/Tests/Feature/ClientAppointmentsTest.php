<?php

declare(strict_types=1);

namespace Domain\Orchestrator\ClientAppointments\Tests\Feature;

use Domain\Client\Models\Client;
use Domain\Notification\Jobs\SendPushToTenant;
use Domain\Schedule\Enums\BookingStatus;
use Domain\Schedule\Models\BookingRequest;
use Domain\Schedule\Tests\Concerns\ScheduleFixtures;
use Domain\Settings\Models\Image;
use Domain\Settings\Models\ProfileSettings;
use Domain\Tenant\Context\CurrentTenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
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

    public function test_each_appointment_shows_the_logo_of_its_barbershop(): void
    {
        // Arrange
        $rafael = $this->barbershop();
        app(CurrentTenant::class)->run($rafael->tenant_id, function (): void {
            $image = Image::query()->create(['path' => 'images/logo.webp', 'mime' => 'image/webp', 'size' => 1024, 'width' => 400, 'height' => 400]);
            ProfileSettings::query()->firstOrFail()->update(['avatar_image_id' => $image->id]);
        });
        $ana = Client::factory()->registered()->create();
        $this->bookingRequest($rafael, '2026-10-21', '15:00', BookingStatus::Confirmed, attributes: ['client_id' => $ana->id]);
        Passport::actingAs($ana, [], 'client');

        // Act
        $response = $this->getJson('/api/client/appointments');

        // Assert
        $response->assertOk()->assertJsonPath('0.barbershop.avatar_url', '/storage/images/logo.webp');
    }

    public function test_the_client_cancels_an_own_appointment_the_time_is_free_again_and_the_barber_is_told(): void
    {
        // Arrange
        Bus::fake([SendPushToTenant::class]);
        $rafael = $this->barbershop();
        $ana = Client::factory()->registered()->create();
        $booking = $this->bookingRequest($rafael, '2026-10-20', '10:00', BookingStatus::Confirmed, attributes: ['client_id' => $ana->id]);
        Passport::actingAs($ana, [], 'client');

        // Act
        $response = $this->postJson("/api/client/appointments/{$booking->id}/cancel");

        // Assert
        $response->assertNoContent();
        $this->assertSame(BookingStatus::Cancelled, BookingRequest::withoutTenantScope()->findOrFail($booking->id)->status);
        Bus::assertDispatched(SendPushToTenant::class, fn (SendPushToTenant $job): bool => $job->data->tenant_id === $rafael->tenant_id
            && $job->data->message->title === 'Horário cancelado pelo cliente');
        $times = collect($this->getJson('/api/public/rafael/availability?service_ids[]='.$this->serviceId($rafael, 'Corte'))->json('days'))
            ->firstWhere('date', '2026-10-20')['times'];
        $this->assertContains('10:00', $times);
    }

    public function test_a_client_cannot_cancel_someone_else_s_appointment(): void
    {
        // Arrange
        $rafael = $this->barbershop();
        $other = Client::factory()->create();
        $booking = $this->bookingRequest($rafael, '2026-10-20', '10:00', BookingStatus::Confirmed, attributes: ['client_id' => $other->id]);
        Passport::actingAs(Client::factory()->registered()->create(), [], 'client');

        // Act
        $response = $this->postJson("/api/client/appointments/{$booking->id}/cancel");

        // Assert
        $response->assertNotFound();
        $this->assertSame(BookingStatus::Confirmed, BookingRequest::withoutTenantScope()->findOrFail($booking->id)->status);
    }

    public function test_cancelling_is_refused_close_to_the_time_and_for_a_finished_appointment(): void
    {
        // Arrange: now is Monday 08:00 (São Paulo); the limit is 2 hours before.
        $rafael = $this->barbershop();
        $ana = Client::factory()->registered()->create();
        $soon = $this->bookingRequest($rafael, '2026-10-19', '09:30', BookingStatus::Confirmed, attributes: ['client_id' => $ana->id]);
        $done = $this->bookingRequest($rafael, '2026-10-12', '10:00', BookingStatus::Completed, attributes: ['client_id' => $ana->id]);
        Passport::actingAs($ana, [], 'client');

        // Act
        $tooLate = $this->postJson("/api/client/appointments/{$soon->id}/cancel");
        $finished = $this->postJson("/api/client/appointments/{$done->id}/cancel");

        // Assert
        $tooLate->assertStatus(409)->assertJsonPath('code', 'CANCELLATION_TOO_LATE');
        $finished->assertStatus(409)->assertJsonPath('code', 'INVALID_STATUS_TRANSITION');
        $this->assertSame(BookingStatus::Confirmed, BookingRequest::withoutTenantScope()->findOrFail($soon->id)->status);
    }
}
