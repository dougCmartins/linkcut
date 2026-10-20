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

    public function test_lists_one_page_newest_first_with_the_totals_of_the_whole_period(): void
    {
        // Arrange
        $owner = $this->actingAsBarber();
        foreach (range(1, 60) as $day) {
            $this->revenue($owner, ['date' => sprintf('2026-08-%02d', min($day, 31)), 'time' => sprintf('%02d:00', 8 + $day % 10)]);
        }
        $this->revenue($owner, ['date' => '2026-07-31', 'price_in_cents' => 9999]);

        // Act
        $response = $this->getJson('/api/finance/history?from=2026-08-01&to=2026-08-31&page=2');

        // Assert
        $response->assertOk();
        $response->assertJsonCount(10, 'items');
        $response->assertJsonPath('page', 2);
        $response->assertJsonPath('per_page', 50);
        $response->assertJsonPath('last_page', 2);
        $response->assertJsonPath('summary', ['total_in_cents' => 300_000, 'count' => 60, 'average_ticket_in_cents' => 5000]);
        $first = $this->getJson('/api/finance/history?from=2026-08-01&to=2026-08-31')->json('items.0');
        $this->assertSame('2026-08-31', $first['date']);
        $this->assertSame(['id', 'date', 'time', 'client_name', 'service_name', 'price_in_cents', 'cost_in_cents'], array_keys($first));
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
        $response->assertJsonPath('last_page', 1);
    }

    public function test_validates_the_period_and_the_page_size(): void
    {
        // Arrange
        $this->actingAsBarber();

        // Act
        $response = $this->getJson('/api/finance/history?from=2026-08-31&to=2026-08-01&per_page=30');
        $missing = $this->getJson('/api/finance/history');

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonPath('errors.to.0', 'after_or_equal');
        $response->assertJsonPath('errors.per_page.0', 'in');
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

    public function test_exports_the_whole_period_as_a_csv_for_excel_in_portuguese(): void
    {
        // Arrange
        $owner = $this->actingAsBarber();
        $this->revenue($owner, ['date' => '2026-08-10', 'time' => '09:00', 'client_name' => '=HYPERLINK("x")', 'price_in_cents' => 6550]);
        $this->revenue($owner, ['date' => '2026-08-11', 'time' => '14:30', 'client_name' => 'João; Silva']);
        foreach (range(1, 60) as $index) {
            $this->revenue($owner, ['date' => '2026-08-01']);
        }

        // Act
        $response = $this->get('/api/finance/history/export?from=2026-08-01&to=2026-08-31');

        // Assert
        $response->assertOk();
        $this->assertStringContainsString('historico-linkcut_2026-08-01_a_2026-08-31.csv', (string) $response->headers->get('Content-Disposition'));
        $csv = $response->streamedContent();
        $lines = explode("\r\n", substr($csv, 3));
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertSame('Data;Hora;Cliente;Serviço;"Valor (R$)"', $lines[0]);
        $this->assertSame('11/08/2026;14:30;"João; Silva";Corte;50,00', $lines[1]);
        $this->assertSame('10/08/2026;09:00;"\'=HYPERLINK(""x"")";Corte;65,50', $lines[2]);
        $this->assertCount(1 + 62 + 1, $lines);
    }

    public function test_the_history_requires_a_session(): void
    {
        // Act
        $response = $this->getJson('/api/finance/history?from=2026-08-01&to=2026-08-31');

        // Assert
        $response->assertUnauthorized();
    }
}
