<?php

declare(strict_types=1);

namespace Domain\Finance\Tests\Feature;

use Domain\Finance\Actions\RecordServiceRevenue;
use Domain\Finance\Actions\RemoveServiceRevenue;
use Domain\Finance\Data\BookingRequestIdData;
use Domain\Finance\Data\RecordRevenueData;
use Domain\Finance\Models\RevenueEntry;
use Domain\Identity\Models\User;
use Domain\Tenant\Context\CurrentTenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ServiceRevenueTest extends TestCase
{
    use RefreshDatabase;

    private function concluded(int $priceInCents = 5000): RecordRevenueData
    {
        return new RecordRevenueData(
            booking_request_id: 42,
            date: '2026-10-15',
            time: '10:00',
            client_name: 'Ana Souza',
            service_name: 'Corte',
            price_in_cents: $priceInCents,
            cost_in_cents: 600,
        );
    }

    public function test_concluding_the_same_request_twice_keeps_one_entry(): void
    {
        // Arrange
        $tenantId = User::factory()->create()->tenant_id;

        // Act
        $count = app(CurrentTenant::class)->run($tenantId, function (): int {
            app(RecordServiceRevenue::class)->handle($this->concluded());
            app(RecordServiceRevenue::class)->handle($this->concluded(6000));

            return RevenueEntry::query()->where('price_in_cents', 6000)->count() + RevenueEntry::query()->count();
        });

        // Assert
        $this->assertSame(2, $count);
    }

    public function test_undo_removes_the_entry_of_the_request(): void
    {
        // Arrange
        $tenantId = User::factory()->create()->tenant_id;

        // Act
        $exists = app(CurrentTenant::class)->run($tenantId, function (): bool {
            app(RecordServiceRevenue::class)->handle($this->concluded());
            app(RemoveServiceRevenue::class)->handle(new BookingRequestIdData(booking_request_id: 42));
            app(RemoveServiceRevenue::class)->handle(new BookingRequestIdData(booking_request_id: 42));

            return RevenueEntry::query()->exists();
        });

        // Assert
        $this->assertFalse($exists);
    }
}
