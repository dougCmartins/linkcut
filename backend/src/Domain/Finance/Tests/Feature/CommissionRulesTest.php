<?php

declare(strict_types=1);

namespace Domain\Finance\Tests\Feature;

use Domain\Finance\Models\CommissionEntry;
use Domain\Finance\Models\CommissionRule;
use Domain\Identity\Enums\UserRole;
use Domain\Identity\Models\User;
use Domain\Schedule\Enums\BookingStatus;
use Domain\Schedule\Tests\Concerns\ScheduleFixtures;
use Domain\Team\Models\Staff;
use Domain\Team\Models\StaffPermission;
use Domain\Tenant\Context\CurrentTenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

final class CommissionRulesTest extends TestCase
{
    use RefreshDatabase;
    use ScheduleFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->freezeMondayMorning('18:00');
    }

    public function test_staff_exception_beats_general_rule_and_changing_rule_does_not_rewrite_entries(): void
    {
        // Arrange
        $owner = $this->barbershop();
        $serviceId = $this->serviceId($owner, 'Corte');
        $staffId = $this->ownerStaffId($owner);
        $this->actingAsOwner($owner);

        $this->postJson('/api/finance/commission/rules', [
            'service_id' => $serviceId,
            'staff_id' => null,
            'percent' => 500,
            'starts_on' => '2026-01-01',
        ])->assertSuccessful();

        $this->postJson('/api/finance/commission/rules', [
            'service_id' => $serviceId,
            'staff_id' => $staffId,
            'percent' => 400,
            'starts_on' => '2026-01-01',
        ])->assertSuccessful();

        $request = $this->bookingRequest($owner, '2026-10-19', '15:00', BookingStatus::Confirmed);
        $this->postJson("/api/schedule/requests/{$request->id}/complete")->assertSuccessful();

        $amountBefore = app(CurrentTenant::class)->run(
            $owner->tenant_id,
            fn () => (int) CommissionEntry::query()->where('booking_request_id', $request->id)->value('amount_in_cents'),
        );

        // Act — new general rule at 90% must not change the snapshot
        $this->postJson('/api/finance/commission/rules', [
            'service_id' => $serviceId,
            'staff_id' => null,
            'percent' => 900,
            'starts_on' => '2026-10-20',
        ])->assertSuccessful();

        $amountAfter = app(CurrentTenant::class)->run(
            $owner->tenant_id,
            fn () => (int) CommissionEntry::query()->where('booking_request_id', $request->id)->value('amount_in_cents'),
        );

        // Assert — staff exception 40% of R$ 50 = R$ 20
        $this->assertSame(2_000, $amountBefore);
        $this->assertSame($amountBefore, $amountAfter);
        $this->assertSame(2_000, $amountAfter);
    }

    public function test_barber_cannot_manage_rules(): void
    {
        // Arrange
        $owner = $this->barbershop();
        $barber = User::factory()->create([
            'tenant_id' => $owner->tenant_id,
            'organization_id' => $owner->organization_id,
            'role' => UserRole::Barber,
        ]);
        app(CurrentTenant::class)->run($owner->tenant_id, function () use ($barber): void {
            $staff = Staff::query()->create(['user_id' => $barber->id, 'name' => 'B', 'active' => true]);
            StaffPermission::query()->create(['staff_id' => $staff->id]);
        });
        Passport::actingAs($barber);

        // Act
        $response = $this->getJson('/api/finance/commission/rules');

        // Assert
        $response->assertForbidden()->assertJsonPath('code', 'OWNER_REQUIRED');
    }

    public function test_tenant_isolation_on_rules_list(): void
    {
        // Arrange
        $owner = $this->barbershop();
        $other = $this->barbershop('outro');
        $serviceId = $this->serviceId($owner, 'Corte');
        $this->actingAsOwner($owner);
        $this->postJson('/api/finance/commission/rules', [
            'service_id' => $serviceId,
            'staff_id' => null,
            'percent' => 500,
            'starts_on' => '2026-01-01',
        ])->assertSuccessful();

        // Act
        $this->actingAsOwner($other);
        $list = $this->getJson('/api/finance/commission/rules');

        // Assert
        $list->assertSuccessful();
        $this->assertSame([], $list->json());
        $foreignCount = CommissionRule::withoutTenantScope()->where('tenant_id', $owner->tenant_id)->count();
        $this->assertSame(1, $foreignCount);
    }
}
