<?php

declare(strict_types=1);

namespace Domain\Orchestrator\CompleteAppointment\Tests\Feature;

use Domain\Finance\Models\CommissionEntry;
use Domain\Schedule\Enums\BookingStatus;
use Domain\Schedule\Tests\Concerns\ScheduleFixtures;
use Domain\Tenant\Context\CurrentTenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CommissionOnCompleteTest extends TestCase
{
    use RefreshDatabase;
    use ScheduleFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->freezeMondayMorning('18:00');
    }

    public function test_completing_records_commission_entry_and_reopen_removes_it(): void
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
        $request = $this->bookingRequest($owner, '2026-10-19', '15:00', BookingStatus::Confirmed);

        // Act
        $this->postJson("/api/schedule/requests/{$request->id}/complete")->assertSuccessful();
        $exists = app(CurrentTenant::class)->run(
            $owner->tenant_id,
            fn () => CommissionEntry::query()->where('booking_request_id', $request->id)->exists(),
        );
        $amount = app(CurrentTenant::class)->run(
            $owner->tenant_id,
            fn () => (int) CommissionEntry::query()->where('booking_request_id', $request->id)->value('amount_in_cents'),
        );
        $this->postJson("/api/schedule/requests/{$request->id}/reopen")->assertSuccessful();
        $after = app(CurrentTenant::class)->run(
            $owner->tenant_id,
            fn () => CommissionEntry::query()->where('booking_request_id', $request->id)->exists(),
        );

        // Assert
        $this->assertTrue($exists);
        $this->assertSame(2_500, $amount); // 50% of R$ 50
        $this->assertFalse($after);
    }

    public function test_closed_period_blocks_reopen_until_owner_reopens_closing(): void
    {
        // Arrange
        $owner = $this->barbershop();
        $this->actingAsOwner($owner);
        $request = $this->bookingRequest($owner, '2026-10-19', '15:00', BookingStatus::Confirmed);
        $this->postJson("/api/schedule/requests/{$request->id}/complete")->assertSuccessful();
        $closing = $this->postJson('/api/finance/commission/closings', [
            'from' => '2026-10-01',
            'to' => '2026-10-31',
        ])->assertSuccessful()->json();

        // Act
        $blocked = $this->postJson("/api/schedule/requests/{$request->id}/reopen");
        $this->postJson("/api/finance/commission/closings/{$closing['id']}/reopen")->assertSuccessful();
        $allowed = $this->postJson("/api/schedule/requests/{$request->id}/reopen");

        // Assert
        $blocked->assertStatus(409)->assertJsonPath('code', 'COMMISSION_PERIOD_CLOSED');
        $allowed->assertSuccessful()->assertJsonPath('status', 'confirmed');
    }

    public function test_appbarber_style_after_adjustment_on_completion(): void
    {
        // Arrange — R$ 50 price, 50%, after 4% on commission → R$ 24 (half of the R$ 100 example)
        $owner = $this->barbershop();
        $serviceId = $this->serviceId($owner, 'Corte');
        $this->actingAsOwner($owner);
        $this->postJson('/api/finance/commission/rules', [
            'service_id' => $serviceId,
            'staff_id' => null,
            'percent' => 500,
            'starts_on' => '2026-01-01',
        ])->assertSuccessful();
        $this->postJson('/api/finance/commission/adjustments', [
            'staff_id' => null,
            'name' => 'Taxa cartão',
            'stage' => 'after',
            'kind' => 'percent',
            'applies_to' => 'commission',
            'value' => 40,
            'active' => true,
            'starts_on' => '2026-01-01',
        ])->assertSuccessful();
        $request = $this->bookingRequest($owner, '2026-10-19', '15:00', BookingStatus::Confirmed);

        // Act
        $this->postJson("/api/schedule/requests/{$request->id}/complete")->assertSuccessful();
        $amount = app(CurrentTenant::class)->run(
            $owner->tenant_id,
            fn () => (int) CommissionEntry::query()->where('booking_request_id', $request->id)->value('amount_in_cents'),
        );

        // Assert — 2500 * 0.96 = 2400
        $this->assertSame(2_400, $amount);
    }
}
