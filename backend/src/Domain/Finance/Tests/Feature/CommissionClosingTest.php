<?php

declare(strict_types=1);

namespace Domain\Finance\Tests\Feature;

use Domain\Finance\Models\CommissionClosing;
use Domain\Finance\Models\CommissionEntry;
use Domain\Schedule\Enums\BookingStatus;
use Domain\Schedule\Tests\Concerns\ScheduleFixtures;
use Domain\Tenant\Context\CurrentTenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CommissionClosingTest extends TestCase
{
    use RefreshDatabase;
    use ScheduleFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->freezeMondayMorning('18:00');
    }

    public function test_close_links_entries_and_reopen_clears_them(): void
    {
        // Arrange
        $owner = $this->barbershop();
        $this->actingAsOwner($owner);
        $request = $this->bookingRequest($owner, '2026-10-19', '15:00', BookingStatus::Confirmed);
        $this->postJson("/api/schedule/requests/{$request->id}/complete")->assertSuccessful();

        // Act
        $closing = $this->postJson('/api/finance/commission/closings', [
            'from' => '2026-10-01',
            'to' => '2026-10-31',
        ])->assertSuccessful()->json();

        $linked = app(CurrentTenant::class)->run(
            $owner->tenant_id,
            fn () => (int) CommissionEntry::query()->where('booking_request_id', $request->id)->value('closing_id'),
        );

        $this->postJson("/api/finance/commission/closings/{$closing['id']}/reopen")->assertSuccessful();
        $cleared = app(CurrentTenant::class)->run(
            $owner->tenant_id,
            fn () => CommissionEntry::query()->where('booking_request_id', $request->id)->value('closing_id'),
        );

        // Assert
        $this->assertSame($closing['id'], $linked);
        $this->assertNull($cleared);
    }

    public function test_closing_of_another_tenant_is_invisible(): void
    {
        // Arrange
        $owner = $this->barbershop();
        $other = $this->barbershop('filial-x');
        $this->actingAsOwner($owner);
        $this->postJson('/api/finance/commission/closings', [
            'from' => '2026-10-01',
            'to' => '2026-10-31',
        ])->assertSuccessful();

        // Act
        $this->actingAsOwner($other);
        $list = $this->getJson('/api/finance/commission/closings');

        // Assert
        $list->assertSuccessful();
        $this->assertSame([], $list->json());
        $this->assertSame(1, CommissionClosing::withoutTenantScope()->where('tenant_id', $owner->tenant_id)->count());
    }
}
