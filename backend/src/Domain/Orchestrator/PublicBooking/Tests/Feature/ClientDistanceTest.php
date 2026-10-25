<?php

declare(strict_types=1);

namespace Domain\Orchestrator\PublicBooking\Tests\Feature;

use Domain\Schedule\Tests\Concerns\ScheduleFixtures;
use Domain\Settings\Models\ProfileSettings;
use Domain\Tenant\Context\CurrentTenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class ClientDistanceTest extends TestCase
{
    use RefreshDatabase;
    use ScheduleFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->freezeMondayMorning();
    }

    /** @return array<string, mixed> */
    private function booking(int $serviceId, array $location = []): array
    {
        return ['client_name' => 'João Silva', 'client_whatsapp' => '11912345678', 'client_email' => 'joao@example.com', 'service_ids' => [$serviceId], 'date' => '2026-10-20', 'time' => '10:00', ...$location];
    }

    public function test_the_barber_sees_how_far_the_client_was_without_the_client_coordinates(): void
    {
        // Arrange: the client is at the MASP, 648 m from the barbershop.
        $owner = $this->barbershop();

        // Act
        $response = $this->postJson('/api/public/rafael/booking-requests', $this->booking(
            $this->serviceId($owner, 'Corte'),
            ['client_latitude' => -23.5614, 'client_longitude' => -46.6559],
        ));

        // Assert
        $response->assertCreated();
        $this->assertStringNotContainsString('latitude', $response->getContent());
        $this->actingAsOwner($owner);
        $this->getJson('/api/schedule/requests')->assertJsonPath('0.client_distance_meters', 648);
        $this->assertFalse(Schema::hasColumn('booking_requests', 'client_latitude'));
        $this->assertFalse(Schema::hasColumn('booking_requests', 'client_longitude'));
    }

    public function test_a_client_who_did_not_share_the_location_still_books(): void
    {
        // Arrange
        $owner = $this->barbershop();

        // Act
        $response = $this->postJson('/api/public/rafael/booking-requests', $this->booking($this->serviceId($owner, 'Corte')));

        // Assert
        $response->assertCreated();
        $this->actingAsOwner($owner);
        $this->getJson('/api/schedule/requests')->assertJsonPath('0.client_distance_meters', null);
    }

    public function test_the_location_comes_with_both_coordinates(): void
    {
        // Arrange
        $serviceId = $this->serviceId($this->barbershop(), 'Corte');

        // Act
        $response = $this->postJson('/api/public/rafael/booking-requests', $this->booking($serviceId, ['client_latitude' => -23.5]));

        // Assert
        $response->assertUnprocessable()->assertJsonPath('errors.client_longitude.0', 'required_with');
    }

    public function test_a_barbershop_without_address_cannot_receive_bookings(): void
    {
        // Arrange
        $owner = $this->barbershop();
        app(CurrentTenant::class)->run($owner->tenant_id, fn () => ProfileSettings::query()->update(['address_street' => '']));

        // Act
        $response = $this->postJson('/api/public/rafael/booking-requests', $this->booking($this->serviceId($owner, 'Corte')));

        // Assert
        $response->assertStatus(409)->assertJsonPath('code', 'BARBERSHOP_ADDRESS_MISSING');
        $this->getJson('/api/public/rafael')->assertJsonPath('profile.address', null);
    }
}
