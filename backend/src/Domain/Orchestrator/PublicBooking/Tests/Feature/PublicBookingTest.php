<?php

declare(strict_types=1);

namespace Domain\Orchestrator\PublicBooking\Tests\Feature;

use Domain\Schedule\Enums\BookingStatus;
use Domain\Schedule\Tests\Concerns\ScheduleFixtures;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PublicBookingTest extends TestCase
{
    use RefreshDatabase;
    use ScheduleFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->freezeMondayMorning('13:10');
    }

    /** @return array<string, mixed> */
    private function bookingPayload(int $serviceId, array $overrides = []): array
    {
        return [
            'client_name' => 'João Silva',
            'client_whatsapp' => '(11) 91234-5678',
            'service_id' => $serviceId,
            'date' => '2026-10-20',
            'time' => '10:00',
            ...$overrides,
        ];
    }

    public function test_lists_the_free_times_of_the_whole_booking_window_without_client_data(): void
    {
        // Arrange
        $owner = $this->barbershop();
        $this->bookingRequest($owner, '2026-10-20', '10:00');
        $this->bookingRequest($owner, '2026-10-20', '14:00', BookingStatus::Rejected);

        // Act
        $response = $this->getJson('/api/public/rafael/availability?service_id='.$this->serviceId($owner, 'Corte'));

        // Assert
        $response->assertOk();
        $days = collect($response->json('days'))->keyBy('date');
        $this->assertSame(['2026-10-19', '2026-10-25'], [$days->keys()->first(), $days->keys()->last()]);
        $this->assertSame('13:30', $days['2026-10-19']['times'][0]);
        $this->assertSame([], $days['2026-10-25']['times']);
        $this->assertSame('13:00', $days['2026-10-24']['times'][array_key_last($days['2026-10-24']['times'])]);
        $this->assertNotContains('10:00', $days['2026-10-20']['times']);
        $this->assertNotContains('09:30', $days['2026-10-20']['times']);
        $this->assertNotContains('10:30', $days['2026-10-20']['times']);
        $this->assertContains('11:00', $days['2026-10-20']['times']);
        $this->assertContains('14:00', $days['2026-10-20']['times']);
        $this->assertStringNotContainsString('Ana', $response->getContent());
    }

    public function test_without_a_service_it_uses_the_shortest_one_and_an_unknown_service_is_refused(): void
    {
        // Arrange
        $owner = $this->barbershop();

        // Act
        $default = $this->getJson('/api/public/rafael/availability');
        $unknown = $this->getJson('/api/public/rafael/availability?service_id=999');

        // Assert
        $default->assertJsonPath('service_id', $this->serviceId($owner, 'Barba'));
        $unknown->assertUnprocessable()->assertJsonPath('errors.service_id.0', 'exists');
    }

    public function test_a_client_requests_a_free_time_and_it_reaches_the_barber_as_pending(): void
    {
        // Arrange
        $owner = $this->barbershop();

        // Act
        $response = $this->postJson('/api/public/rafael/booking-requests', $this->bookingPayload($this->serviceId($owner, 'Corte')));

        // Assert
        $response->assertCreated();
        $response->assertJson(['service_name' => 'Corte', 'date' => '2026-10-20', 'time' => '10:00', 'status' => 'pending']);
        $this->actingAsOwner($owner);
        $pending = $this->getJson('/api/schedule/requests')->json('0');
        $this->assertSame(['João Silva', '11912345678', '10:45'], [$pending['client_name'], $pending['client_whatsapp'], $pending['ends_at']]);
    }

    public function test_the_second_request_for_the_same_time_gets_slot_unavailable(): void
    {
        // Arrange
        $owner = $this->barbershop();
        $payload = $this->bookingPayload($this->serviceId($owner, 'Corte'));
        $this->postJson('/api/public/rafael/booking-requests', $payload)->assertCreated();

        // Act
        $second = $this->postJson('/api/public/rafael/booking-requests', [...$payload, 'client_name' => 'Outro Cliente', 'time' => '10:30']);

        // Assert
        $second->assertStatus(409)->assertJsonPath('code', 'SLOT_UNAVAILABLE');
    }

    public function test_refuses_times_outside_the_free_times(): void
    {
        // Arrange
        $serviceId = $this->serviceId($this->barbershop(), 'Corte');

        // Act
        $past = $this->postJson('/api/public/rafael/booking-requests', $this->bookingPayload($serviceId, ['date' => '2026-10-19', 'time' => '09:00']));
        $sunday = $this->postJson('/api/public/rafael/booking-requests', $this->bookingPayload($serviceId, ['date' => '2026-10-25']));
        $offGrid = $this->postJson('/api/public/rafael/booking-requests', $this->bookingPayload($serviceId, ['time' => '10:10']));
        $beyondWindow = $this->postJson('/api/public/rafael/booking-requests', $this->bookingPayload($serviceId, ['date' => '2026-10-27']));

        // Assert
        foreach ([$past, $sunday, $offGrid, $beyondWindow] as $response) {
            $response->assertStatus(409)->assertJsonPath('code', 'SLOT_UNAVAILABLE');
        }
    }

    public function test_validates_the_client_name_and_whatsapp(): void
    {
        // Arrange
        $serviceId = $this->serviceId($this->barbershop(), 'Corte');

        // Act
        $response = $this->postJson('/api/public/rafael/booking-requests', $this->bookingPayload($serviceId, ['client_name' => 'J', 'client_whatsapp' => '9999']));

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonPath('errors.client_name.0', 'min');
        $response->assertJsonPath('errors.client_whatsapp.0', 'phone');
    }

    public function test_a_booking_never_lands_in_another_barbershop(): void
    {
        // Arrange
        $rafael = $this->barbershop();
        $navalha = $this->barbershop('navalha');

        // Act
        $this->postJson('/api/public/navalha/booking-requests', $this->bookingPayload($this->serviceId($navalha, 'Corte')))->assertCreated();
        $wrongService = $this->postJson('/api/public/navalha/booking-requests', $this->bookingPayload($this->serviceId($rafael, 'Barba')));

        // Assert
        $wrongService->assertUnprocessable()->assertJsonPath('errors.service_id.0', 'exists');
        $this->actingAsOwner($rafael);
        $this->getJson('/api/schedule/requests')->assertExactJson([]);
    }
}
