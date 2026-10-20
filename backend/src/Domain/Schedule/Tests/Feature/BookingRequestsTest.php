<?php

declare(strict_types=1);

namespace Domain\Schedule\Tests\Feature;

use Domain\Schedule\Enums\BookingStatus;
use Domain\Schedule\Tests\Concerns\ScheduleFixtures;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class BookingRequestsTest extends TestCase
{
    use RefreshDatabase;
    use ScheduleFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->freezeMondayMorning();
    }

    public function test_lists_the_pending_requests_from_today_on_in_date_and_time_order(): void
    {
        // Arrange
        $owner = $this->barbershop();
        $this->bookingRequest($owner, '2026-10-20', '16:30');
        $this->bookingRequest($owner, '2026-10-20', '10:00');
        $this->bookingRequest($owner, '2026-10-19', '18:00');
        $this->bookingRequest($owner, '2026-10-18', '10:00');
        $this->bookingRequest($owner, '2026-10-21', '10:00', BookingStatus::Confirmed);
        $this->actingAsOwner($owner);

        // Act
        $response = $this->getJson('/api/schedule/requests?status=pending');

        // Assert
        $response->assertOk();
        $this->assertSame(
            [['2026-10-19', '18:00'], ['2026-10-20', '10:00'], ['2026-10-20', '16:30']],
            array_map(fn (array $item): array => [$item['date'], $item['starts_at']], $response->json()),
        );
        $response->assertJsonPath('0.client_whatsapp', '11912345678');
        $response->assertJsonPath('0.ends_at', '18:45');
    }

    public function test_today_agenda_has_the_confirmed_and_concluded_appointments_of_today(): void
    {
        // Arrange
        $owner = $this->barbershop();
        $this->bookingRequest($owner, '2026-10-19', '15:00', BookingStatus::Completed);
        $this->bookingRequest($owner, '2026-10-19', '09:00', BookingStatus::Confirmed);
        $this->bookingRequest($owner, '2026-10-19', '11:00');
        $this->bookingRequest($owner, '2026-10-20', '09:00', BookingStatus::Confirmed);
        $this->actingAsOwner($owner);

        // Act
        $response = $this->getJson('/api/schedule/today');

        // Assert
        $this->assertSame([['09:00', 'confirmed'], ['15:00', 'completed']], array_map(
            fn (array $item): array => [$item['starts_at'], $item['status']],
            $response->json(),
        ));
    }

    public function test_accepts_and_rejects_only_pending_requests(): void
    {
        // Arrange
        $owner = $this->barbershop();
        $first = $this->bookingRequest($owner, '2026-10-20', '10:00');
        $second = $this->bookingRequest($owner, '2026-10-20', '14:00');
        $this->actingAsOwner($owner);

        // Act
        $accepted = $this->patchJson("/api/schedule/requests/{$first->id}/accept");
        $rejected = $this->patchJson("/api/schedule/requests/{$second->id}/reject");
        $acceptAgain = $this->patchJson("/api/schedule/requests/{$first->id}/accept");
        $acceptRejected = $this->patchJson("/api/schedule/requests/{$second->id}/accept");

        // Assert
        $accepted->assertOk()->assertJsonPath('status', 'confirmed');
        $rejected->assertOk()->assertJsonPath('status', 'rejected');
        $acceptAgain->assertStatus(409)->assertJsonPath('code', 'INVALID_STATUS_TRANSITION');
        $acceptRejected->assertStatus(409);
    }

    public function test_a_barbershop_never_sees_or_answers_the_requests_of_another(): void
    {
        // Arrange
        $other = $this->barbershop('navalha');
        $foreign = $this->bookingRequest($other, '2026-10-20', '10:00');
        $this->actingAsOwner($this->barbershop());

        // Act
        $list = $this->getJson('/api/schedule/requests');
        $accept = $this->patchJson("/api/schedule/requests/{$foreign->id}/accept");

        // Assert
        $list->assertOk()->assertExactJson([]);
        $accept->assertNotFound()->assertJsonPath('code', 'BOOKING_REQUEST_NOT_FOUND');
    }

    public function test_the_agenda_requires_a_session(): void
    {
        // Act
        $response = $this->getJson('/api/schedule/today');

        // Assert
        $response->assertUnauthorized();
    }
}
