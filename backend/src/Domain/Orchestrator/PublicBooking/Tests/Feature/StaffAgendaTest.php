<?php

declare(strict_types=1);

namespace Domain\Orchestrator\PublicBooking\Tests\Feature;

use Domain\Identity\Enums\UserRole;
use Domain\Identity\Models\User;
use Domain\Schedule\Enums\BookingStatus;
use Domain\Schedule\Tests\Concerns\ScheduleFixtures;
use Domain\Team\Enums\StaffTimeBlockRecurrence;
use Domain\Team\Models\Staff;
use Domain\Team\Models\StaffPermission;
use Domain\Team\Models\StaffService;
use Domain\Team\Models\StaffTimeBlock;
use Domain\Team\Support\SeedStaffSchedule;
use Domain\Tenant\Context\CurrentTenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\Passport;
use Tests\TestCase;

final class StaffAgendaTest extends TestCase
{
    use RefreshDatabase;
    use ScheduleFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->freezeMondayMorning('08:00');
    }

    public function test_same_staff_slot_rejects_second_booking_while_another_staff_accepts(): void
    {
        // Arrange
        $owner = $this->barbershop();
        [$staffA, $staffB] = $this->twoActiveBarbers($owner);
        $serviceId = $this->serviceId($owner, 'Corte');

        // Act
        $first = $this->postJson('/api/public/rafael/booking-requests', [
            'client_name' => 'Cliente A',
            'client_whatsapp' => '11911111111',
            'client_email' => 'a@example.com',
            'service_ids' => [$serviceId],
            'date' => '2026-10-20',
            'time' => '10:00',
            'staff_id' => $staffA,
        ]);
        $secondSame = $this->postJson('/api/public/rafael/booking-requests', [
            'client_name' => 'Cliente B',
            'client_whatsapp' => '11922222222',
            'client_email' => 'b@example.com',
            'service_ids' => [$serviceId],
            'date' => '2026-10-20',
            'time' => '10:00',
            'staff_id' => $staffA,
        ]);
        $otherStaff = $this->postJson('/api/public/rafael/booking-requests', [
            'client_name' => 'Cliente C',
            'client_whatsapp' => '11933333333',
            'client_email' => 'c@example.com',
            'service_ids' => [$serviceId],
            'date' => '2026-10-20',
            'time' => '10:00',
            'staff_id' => $staffB,
        ]);

        // Assert
        $first->assertCreated();
        $secondSame->assertStatus(409)->assertJsonPath('code', 'SLOT_UNAVAILABLE');
        $otherStaff->assertCreated();
        $this->assertSame($staffA, (int) DB::table('booking_requests')->where('id', $first->json('id'))->value('staff_id'));
        $this->assertSame($staffB, (int) DB::table('booking_requests')->where('id', $otherStaff->json('id'))->value('staff_id'));
    }

    public function test_weekly_time_block_removes_slots_from_availability(): void
    {
        // Arrange
        $owner = $this->barbershop();
        $staffId = $this->ownerStaffId($owner);
        app(CurrentTenant::class)->run($owner->tenant_id, function () use ($staffId): void {
            StaffTimeBlock::query()->create([
                'staff_id' => $staffId,
                'starts_at' => '2026-10-01 14:00:00',
                'ends_at' => '2026-10-01 18:00:00',
                'recurrence' => StaffTimeBlockRecurrence::Weekly,
                'weekdays' => [2], // Tuesday 2026-10-20
                'note' => 'Tarde livre',
            ]);
        });

        // Act
        $response = $this->getJson('/api/public/rafael/availability?service_ids[]='.$this->serviceId($owner, 'Corte').'&staff_id='.$staffId);

        // Assert
        $response->assertOk();
        $times = collect($response->json('days'))->keyBy('date')['2026-10-20']['times'];
        $this->assertNotContains('14:00', $times);
        $this->assertNotContains('15:00', $times);
        $this->assertContains('10:00', $times);
        $this->assertContains('13:00', $times);
    }

    public function test_barber_cannot_complete_another_barbers_appointment(): void
    {
        // Arrange
        $owner = $this->barbershop();
        [$staffA, $staffB] = $this->twoActiveBarbers($owner);
        $barberB = $this->userForStaff($owner, $staffB);
        $this->travelTo(now()->setTime(18, 0));
        $request = $this->bookingRequest($owner, '2026-10-19', '15:00', BookingStatus::Confirmed, attributes: ['staff_id' => $staffA]);
        Passport::actingAs($barberB);

        // Act
        $response = $this->postJson("/api/schedule/requests/{$request->id}/complete");

        // Assert
        $response->assertForbidden()->assertJsonPath('code', 'BOOKING_FORBIDDEN');
    }

    public function test_owner_can_reassign_appointment_to_another_staff(): void
    {
        // Arrange
        $owner = $this->barbershop();
        [$staffA, $staffB] = $this->twoActiveBarbers($owner);
        $request = $this->bookingRequest($owner, '2026-10-20', '11:00', BookingStatus::Confirmed, attributes: ['staff_id' => $staffA]);
        $this->actingAsOwner($owner);

        // Act
        $response = $this->postJson("/api/schedule/requests/{$request->id}/reassign", ['staff_id' => $staffB]);

        // Assert
        $response->assertSuccessful()->assertJsonPath('staff_id', $staffB);
    }

    public function test_any_barber_goes_to_the_free_barber_with_fewer_bookings_that_day(): void
    {
        // Arrange
        $owner = $this->barbershop();
        [$busyBarber, $quietBarber] = $this->twoActiveBarbers($owner);
        foreach (['09:00', '10:00', '11:00'] as $time) {
            $this->bookingRequest($owner, '2026-10-20', $time, BookingStatus::Confirmed, attributes: ['staff_id' => $busyBarber]);
        }
        $this->bookingRequest($owner, '2026-10-20', '09:00', BookingStatus::Confirmed, attributes: ['staff_id' => $quietBarber]);

        // Act
        $response = $this->postJson('/api/public/rafael/booking-requests', [
            'client_name' => 'Cliente Qualquer',
            'client_whatsapp' => '11944444444',
            'client_email' => 'qualquer@example.com',
            'service_ids' => [$this->serviceId($owner, 'Corte')],
            'date' => '2026-10-20',
            'time' => '15:00',
        ]);

        // Assert
        $response->assertCreated()->assertJsonPath('staff_name', 'Segundo');
        $this->assertSame($quietBarber, (int) DB::table('booking_requests')->where('id', $response->json('id'))->value('staff_id'));
    }

    public function test_public_staff_lists_only_barbers_who_do_every_chosen_service(): void
    {
        // Arrange
        $owner = $this->barbershop();
        [$ownerStaff, $other] = $this->twoActiveBarbers($owner);
        $barba = $this->serviceId($owner, 'Barba');
        app(CurrentTenant::class)->run($owner->tenant_id, fn () => StaffService::query()->where('staff_id', $other)->where('service_id', $barba)->delete());

        // Act
        $all = $this->getJson('/api/public/rafael/staff');
        $withBarba = $this->getJson('/api/public/rafael/staff?service_ids[]='.$this->serviceId($owner, 'Corte').'&service_ids[]='.$barba);

        // Assert
        $all->assertOk()->assertJsonCount(2);
        $withBarba->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $ownerStaff);
    }

    /** @return array{0: int, 1: int} */
    private function twoActiveBarbers(User $owner): array
    {
        return app(CurrentTenant::class)->run($owner->tenant_id, function () use ($owner): array {
            $ownerStaff = Staff::query()->where('user_id', $owner->id)->firstOrFail();
            $other = Staff::query()->create([
                'user_id' => null,
                'name' => 'Segundo',
                'active' => true,
            ]);
            StaffPermission::query()->create(['staff_id' => $other->id]);
            SeedStaffSchedule::forStaff($owner->tenant_id, $other->id);
            // Ensure both can do Corte
            foreach ([$ownerStaff->id, $other->id] as $staffId) {
                $serviceIds = StaffService::query()->where('staff_id', $staffId)->pluck('service_id');
                $this->assertNotEmpty($serviceIds);
            }

            return [(int) $ownerStaff->id, (int) $other->id];
        });
    }

    private function userForStaff(User $owner, int $staffId): User
    {
        return app(CurrentTenant::class)->run($owner->tenant_id, function () use ($owner, $staffId): User {
            $barber = User::factory()->create([
                'tenant_id' => $owner->tenant_id,
                'organization_id' => $owner->organization_id,
                'role' => UserRole::Barber,
                'name' => 'Barber B',
                'email' => 'barber-b-'.uniqid().'@example.com',
            ]);
            Staff::query()->whereKey($staffId)->update(['user_id' => $barber->id]);

            return $barber;
        });
    }
}
