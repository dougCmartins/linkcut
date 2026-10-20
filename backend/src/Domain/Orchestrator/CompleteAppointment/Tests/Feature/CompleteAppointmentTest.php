<?php

declare(strict_types=1);

namespace Domain\Orchestrator\CompleteAppointment\Tests\Feature;

use Domain\Schedule\Enums\BookingStatus;
use Domain\Schedule\Tests\Concerns\ScheduleFixtures;
use Domain\Settings\Models\Service;
use Domain\Tenant\Context\CurrentTenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CompleteAppointmentTest extends TestCase
{
    use RefreshDatabase;
    use ScheduleFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->freezeMondayMorning('18:00');
    }

    public function test_concluding_adds_the_current_price_to_the_revenue_and_the_cost_to_the_expenses(): void
    {
        // Arrange
        $owner = $this->barbershop();
        $request = $this->bookingRequest($owner, '2026-10-19', '15:00', BookingStatus::Confirmed);
        app(CurrentTenant::class)->run($owner->tenant_id, fn () => Service::query()->whereKey($request->service_id)->update(['price_in_cents' => 5500]));
        $this->actingAsOwner($owner);

        // Act
        $response = $this->postJson("/api/schedule/requests/{$request->id}/complete");

        // Assert
        $response->assertSuccessful();
        $response->assertJsonPath('request.status', 'completed');
        $response->assertJsonPath('price_in_cents', 5500);
        $response->assertJsonPath('cost_in_cents', 600);
        $month = $this->getJson('/api/finance/month-to-date');
        $month->assertJsonPath('revenue_in_cents', 5500);
        $month->assertJsonPath('expenses.service_costs_in_cents', 600);
        $history = $this->getJson('/api/finance/history?from=2026-10-19&to=2026-10-19')->json('items.0');
        $this->assertSame(['15:00', 'Ana Souza', 'Corte', 5500], [$history['time'], $history['client_name'], $history['service_name'], $history['price_in_cents']]);
    }

    public function test_undo_takes_it_out_of_the_revenue_and_back_to_confirmed(): void
    {
        // Arrange
        $owner = $this->barbershop();
        $request = $this->bookingRequest($owner, '2026-10-19', '15:00', BookingStatus::Confirmed);
        $this->actingAsOwner($owner);
        $this->postJson("/api/schedule/requests/{$request->id}/complete")->assertSuccessful();

        // Act
        $response = $this->postJson("/api/schedule/requests/{$request->id}/reopen");

        // Assert
        $response->assertSuccessful()->assertJsonPath('status', 'confirmed');
        $this->getJson('/api/finance/month-to-date')->assertJsonPath('revenue_in_cents', 0);
        $this->postJson("/api/schedule/requests/{$request->id}/complete")->assertSuccessful();
        $this->getJson('/api/finance/history?from=2026-10-19&to=2026-10-19')->assertJsonPath('summary.count', 1);
    }

    public function test_only_a_confirmed_appointment_of_today_can_be_concluded_and_only_once(): void
    {
        // Arrange
        $owner = $this->barbershop();
        $pending = $this->bookingRequest($owner, '2026-10-19', '10:00');
        $tomorrow = $this->bookingRequest($owner, '2026-10-20', '10:00', BookingStatus::Confirmed);
        $today = $this->bookingRequest($owner, '2026-10-19', '15:00', BookingStatus::Confirmed);
        $this->actingAsOwner($owner);
        $this->postJson("/api/schedule/requests/{$today->id}/complete")->assertSuccessful();

        // Act
        $responses = [
            $this->postJson("/api/schedule/requests/{$pending->id}/complete"),
            $this->postJson("/api/schedule/requests/{$tomorrow->id}/complete"),
            $this->postJson("/api/schedule/requests/{$today->id}/complete"),
            $this->postJson("/api/schedule/requests/{$tomorrow->id}/reopen"),
        ];

        // Assert
        foreach ($responses as $response) {
            $response->assertStatus(409)->assertJsonPath('code', 'INVALID_STATUS_TRANSITION');
        }
        $this->getJson('/api/finance/month-to-date')->assertJsonPath('revenue_in_cents', 5000);
    }

    public function test_a_service_removed_after_the_request_can_still_be_concluded(): void
    {
        // Arrange
        $owner = $this->barbershop();
        $request = $this->bookingRequest($owner, '2026-10-19', '15:00', BookingStatus::Confirmed);
        app(CurrentTenant::class)->run($owner->tenant_id, fn () => Service::query()->whereKey($request->service_id)->delete());
        $this->actingAsOwner($owner);

        // Act
        $response = $this->postJson("/api/schedule/requests/{$request->id}/complete");

        // Assert
        $response->assertSuccessful()->assertJsonPath('price_in_cents', 5000);
    }

    public function test_a_barbershop_never_concludes_an_appointment_of_another(): void
    {
        // Arrange
        $other = $this->barbershop('navalha');
        $foreign = $this->bookingRequest($other, '2026-10-19', '15:00', BookingStatus::Confirmed);
        $this->actingAsOwner($this->barbershop());

        // Act
        $response = $this->postJson("/api/schedule/requests/{$foreign->id}/complete");

        // Assert
        $response->assertNotFound()->assertJsonPath('code', 'BOOKING_REQUEST_NOT_FOUND');
    }
}
