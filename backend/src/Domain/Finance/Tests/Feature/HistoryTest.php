<?php

declare(strict_types=1);

namespace Domain\Finance\Tests\Feature;

use Domain\Finance\Tests\Concerns\FinanceFixtures;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class HistoryTest extends TestCase
{
    use FinanceFixtures;
    use RefreshDatabase;

    public function test_lists_the_newest_rows_first_and_the_cursor_brings_the_next_ones(): void
    {
        // Arrange
        $owner = $this->actingAsBarber();
        foreach (range(1, 60) as $day) {
            $this->revenue($owner, ['date' => sprintf('2026-08-%02d', min($day, 31)), 'time' => sprintf('%02d:00', 8 + $day % 10)]);
        }
        $this->revenue($owner, ['date' => '2026-07-31', 'price_in_cents' => 9999]);

        // Act
        $first = $this->getJson('/api/finance/history?from=2026-08-01&to=2026-08-31');
        $next = $this->getJson('/api/finance/history?from=2026-08-01&to=2026-08-31&cursor='.urlencode((string) $first->json('next_cursor')));

        // Assert
        $first->assertOk()->assertJsonCount(50, 'items');
        $first->assertJsonPath('summary', ['total_in_cents' => 300_000, 'count' => 60, 'average_ticket_in_cents' => 5000]);
        $this->assertSame('2026-08-31', $first->json('items.0.date'));
        $this->assertSame(['id', 'date', 'time', 'client_name', 'service_name', 'price_in_cents', 'cost_in_cents'], array_keys($first->json('items.0')));
        $next->assertOk()->assertJsonCount(10, 'items');
        $next->assertJsonPath('summary', null);
        $next->assertJsonPath('next_cursor', null);
        $ids = [...$first->json('items.*.id'), ...$next->json('items.*.id')];
        $this->assertCount(60, array_unique($ids));
    }

    public function test_an_empty_period_has_zero_totals(): void
    {
        // Arrange
        $this->actingAsBarber();

        // Act
        $response = $this->getJson('/api/finance/history?from=2026-08-01&to=2026-08-31');

        // Assert
        $response->assertOk();
        $response->assertJsonPath('items', []);
        $response->assertJsonPath('summary', ['total_in_cents' => 0, 'count' => 0, 'average_ticket_in_cents' => 0]);
        $response->assertJsonPath('next_cursor', null);
    }

    public function test_validates_the_period_up_to_one_year(): void
    {
        // Arrange
        $this->actingAsBarber();

        // Act
        $reversed = $this->getJson('/api/finance/history?from=2026-08-31&to=2026-08-01');
        $tooLong = $this->getJson('/api/finance/history?from=2025-01-01&to=2026-08-31');
        $oneYear = $this->getJson('/api/finance/history?from=2025-09-01&to=2026-08-31');
        $missing = $this->getJson('/api/finance/history');

        // Assert
        $reversed->assertUnprocessable()->assertJsonPath('errors.to.0', 'after_or_equal');
        $tooLong->assertUnprocessable()->assertJsonPath('errors.to.0', 'period_too_long');
        $oneYear->assertOk();
        $missing->assertJsonPath('errors.from.0', 'required');
    }

    public function test_a_barbershop_never_sees_the_history_of_another(): void
    {
        // Arrange
        $other = $this->actingAsBarber();
        $this->revenue($other, ['date' => '2026-08-10', 'client_name' => 'Cliente de outra barbearia']);
        $this->actingAsBarber();

        // Act
        $response = $this->getJson('/api/finance/history?from=2026-08-01&to=2026-08-31');

        // Assert
        $response->assertJsonPath('summary.count', 0);
        $this->assertStringNotContainsString('outra barbearia', $response->getContent());
    }

    public function test_the_history_requires_a_session(): void
    {
        // Act
        $response = $this->getJson('/api/finance/history?from=2026-08-01&to=2026-08-31');

        // Assert
        $response->assertUnauthorized();
    }
}
