<?php

declare(strict_types=1);

namespace Domain\Orchestrator\CommissionReport\Tests\Feature;

use Domain\Identity\Enums\UserRole;
use Domain\Identity\Models\User;
use Domain\Schedule\Enums\BookingStatus;
use Domain\Schedule\Tests\Concerns\ScheduleFixtures;
use Domain\Team\Models\Staff;
use Domain\Team\Models\StaffPermission;
use Domain\Tenant\Context\CurrentTenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Passport\Passport;
use Tests\TestCase;

final class CommissionReportTest extends TestCase
{
    use RefreshDatabase;
    use ScheduleFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->freezeMondayMorning('18:00');
    }

    public function test_owner_sees_report_and_can_request_csv_email(): void
    {
        // Arrange
        Notification::fake();
        $owner = $this->barbershop();
        $serviceId = $this->serviceId($owner, 'Corte');
        $this->actingAsOwner($owner);
        $this->postJson('/api/finance/commission/rules', [
            'service_id' => $serviceId,
            'staff_id' => null,
            'percent' => 500,
            'starts_on' => '2026-01-01',
        ])->assertSuccessful();
        $request = $this->bookingRequest($owner, '2026-10-19', '15:00', BookingStatus::Confirmed);
        $this->postJson("/api/schedule/requests/{$request->id}/complete")->assertSuccessful();

        // Act
        $report = $this->getJson('/api/finance/commission/report?from=2026-10-01&to=2026-10-31');
        $csv = $this->postJson('/api/finance/commission/report', [
            'from' => '2026-10-01',
            'to' => '2026-10-31',
        ]);

        // Assert
        $report->assertSuccessful();
        $report->assertJsonPath('total_amount_in_cents', 2500);
        $this->assertCount(1, $report->json('entries'));
        $csv->assertSuccessful()->assertJsonStructure(['email']);
    }

    public function test_barber_without_flag_gets_403_and_with_flag_sees_only_own(): void
    {
        // Arrange
        $owner = $this->barbershop();
        $serviceId = $this->serviceId($owner, 'Corte');
        $this->actingAsOwner($owner);
        $this->postJson('/api/finance/commission/rules', [
            'service_id' => $serviceId,
            'staff_id' => null,
            'percent' => 500,
            'starts_on' => '2026-01-01',
        ])->assertSuccessful();

        $barber = User::factory()->create([
            'tenant_id' => $owner->tenant_id,
            'organization_id' => $owner->organization_id,
            'role' => UserRole::Barber,
            'email' => 'b'.uniqid().'@example.com',
        ]);
        $barberStaffId = app(CurrentTenant::class)->run($owner->tenant_id, function () use ($barber): int {
            $staff = Staff::query()->create(['user_id' => $barber->id, 'name' => 'Barber', 'active' => true]);
            StaffPermission::query()->create([
                'staff_id' => $staff->id,
                'see_own_commission' => false,
            ]);

            return $staff->id;
        });

        $ownerRequest = $this->bookingRequest($owner, '2026-10-19', '14:00', BookingStatus::Confirmed);
        $this->postJson("/api/schedule/requests/{$ownerRequest->id}/complete")->assertSuccessful();

        $barberRequest = $this->bookingRequest($owner, '2026-10-19', '15:00', BookingStatus::Confirmed, attributes: [
            'staff_id' => $barberStaffId,
        ]);
        $this->postJson("/api/schedule/requests/{$barberRequest->id}/complete")->assertSuccessful();

        // Act — no permission
        Passport::actingAs($barber);
        $denied = $this->getJson('/api/finance/commission/report?from=2026-10-01&to=2026-10-31');

        // Assert
        $denied->assertForbidden()->assertJsonPath('code', 'COMMISSION_FORBIDDEN');

        // Arrange — grant flag
        app(CurrentTenant::class)->run($owner->tenant_id, function () use ($barberStaffId): void {
            StaffPermission::query()->where('staff_id', $barberStaffId)->update(['see_own_commission' => true]);
        });

        // Act
        $allowed = $this->getJson('/api/finance/commission/report?from=2026-10-01&to=2026-10-31');

        // Assert — only the barber's entry
        $allowed->assertSuccessful();
        $this->assertCount(1, $allowed->json('entries'));
        $this->assertSame($barberStaffId, $allowed->json('entries.0.staff_id'));
    }
}
