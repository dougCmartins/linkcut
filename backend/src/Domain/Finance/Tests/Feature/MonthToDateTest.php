<?php

declare(strict_types=1);

namespace Domain\Finance\Tests\Feature;

use Carbon\CarbonImmutable;
use Domain\Finance\Tests\Concerns\FinanceFixtures;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class MonthToDateTest extends TestCase
{
    use FinanceFixtures;
    use RefreshDatabase;

    public function test_sums_the_revenue_and_the_expenses_from_day_1_until_today(): void
    {
        // Arrange
        $this->travelToLocal('2026-10-15 23:00');
        $owner = $this->actingAsBarber();
        $this->revenue($owner, ['date' => '2026-10-01', 'price_in_cents' => 5000, 'cost_in_cents' => 600]);
        $this->revenue($owner, ['date' => '2026-10-15', 'price_in_cents' => 8000, 'cost_in_cents' => 1000]);
        $this->revenue($owner, ['date' => '2026-09-30', 'price_in_cents' => 99_999, 'cost_in_cents' => 9999]);
        $this->postJson('/api/finance/fixed-expenses', ['name' => 'Aluguel', 'amount_in_cents' => 150_000, 'due_day' => 5]);
        $this->postJson('/api/finance/fixed-expenses', ['name' => 'Hoje', 'amount_in_cents' => 1000, 'due_day' => 15]);
        $this->postJson('/api/finance/fixed-expenses', ['name' => 'DAS', 'amount_in_cents' => 7590, 'due_day' => 20]);
        $this->postJson('/api/finance/one-off-expenses', ['description' => 'Lâminas', 'amount_in_cents' => 2100, 'date' => '2026-10-02']);
        $this->postJson('/api/finance/one-off-expenses', ['description' => 'Setembro', 'amount_in_cents' => 1000, 'date' => '2026-09-30']);

        // Act
        $response = $this->getJson('/api/finance/month-to-date');

        // Assert
        $response->assertOk();
        $response->assertExactJson([
            'from' => '2026-10-01',
            'to' => '2026-10-15',
            'revenue_in_cents' => 13_000,
            'expenses' => [
                'fixed_in_cents' => 151_000,
                'one_off_in_cents' => 2100,
                'service_costs_in_cents' => 1600,
                'total_in_cents' => 154_700,
            ],
        ]);
    }

    public function test_a_fixed_expense_on_day_31_counts_on_the_last_day_of_a_shorter_month(): void
    {
        // Arrange
        $this->travelToLocal('2026-09-30 10:00');
        $this->actingAsBarber();
        $this->postJson('/api/finance/fixed-expenses', ['name' => 'Fim do mês', 'amount_in_cents' => 5000, 'due_day' => 31]);

        // Act
        $response = $this->getJson('/api/finance/month-to-date');

        // Assert
        $response->assertJsonPath('expenses.fixed_in_cents', 5000);
    }

    public function test_editing_or_removing_an_expense_changes_the_month_numbers(): void
    {
        // Arrange
        $this->travelToLocal('2026-10-15 10:00');
        $this->actingAsBarber();
        $id = $this->postJson('/api/finance/fixed-expenses', ['name' => 'Aluguel', 'amount_in_cents' => 1000, 'due_day' => 1])->json('id');

        // Act
        $this->putJson("/api/finance/fixed-expenses/{$id}", ['name' => 'Aluguel', 'amount_in_cents' => 3000, 'due_day' => 1]);
        $afterEdit = $this->getJson('/api/finance/month-to-date')->json('expenses.total_in_cents');
        $this->deleteJson("/api/finance/fixed-expenses/{$id}");
        $afterDelete = $this->getJson('/api/finance/month-to-date')->json('expenses.total_in_cents');

        // Assert
        $this->assertSame(3000, $afterEdit);
        $this->assertSame(0, $afterDelete);
    }

    public function test_the_month_follows_the_timezone_of_the_barbershop(): void
    {
        // Arrange: 01:00 UTC on Nov 1st is still Oct 31st in São Paulo.
        $this->travelTo(CarbonImmutable::parse('2026-11-01 01:00', 'UTC'));
        $this->actingAsBarber();

        // Act
        $response = $this->getJson('/api/finance/month-to-date');

        // Assert
        $response->assertJsonPath('from', '2026-10-01');
        $response->assertJsonPath('to', '2026-10-31');
    }
}
