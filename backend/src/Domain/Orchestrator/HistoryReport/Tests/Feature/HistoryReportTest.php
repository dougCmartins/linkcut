<?php

declare(strict_types=1);

namespace Domain\Orchestrator\HistoryReport\Tests\Feature;

use Domain\Finance\Tests\Concerns\FinanceFixtures;
use Domain\Orchestrator\HistoryReport\Jobs\SendHistoryReport;
use Domain\Orchestrator\HistoryReport\Notifications\HistoryReportNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

final class HistoryReportTest extends TestCase
{
    use FinanceFixtures;
    use RefreshDatabase;

    public function test_the_request_only_queues_the_report_and_answers_at_once(): void
    {
        // Arrange
        Queue::fake();
        $owner = $this->actingAsBarber();

        // Act
        $response = $this->postJson('/api/finance/history/report', ['from' => '2026-08-01', 'to' => '2026-08-31']);

        // Assert
        $response->assertStatus(202);
        $this->assertStringContainsString('@', (string) $response->json('email'));
        $this->assertNotSame($owner->email, $response->json('email'));
        Queue::assertPushed(SendHistoryReport::class, fn (SendHistoryReport $job): bool => $job->tenantId === $owner->tenant_id
            && $job->userId === $owner->id && $job->from === '2026-08-01' && $job->to === '2026-08-31');
    }

    public function test_the_job_e_mails_the_csv_of_the_period_for_excel_in_portuguese(): void
    {
        // Arrange
        Notification::fake();
        $owner = $this->actingAsBarber();
        $this->revenue($owner, ['date' => '2026-08-10', 'time' => '09:00', 'client_name' => '=HYPERLINK("x")', 'price_in_cents' => 6550]);
        $this->revenue($owner, ['date' => '2026-08-11', 'time' => '14:30', 'client_name' => 'João; Silva']);
        $this->revenue($owner, ['date' => '2026-07-31', 'client_name' => 'Fora do período']);
        $other = $this->actingAsBarber();
        $this->revenue($other, ['date' => '2026-08-12', 'client_name' => 'Cliente de outra barbearia']);

        // Act
        app()->call([new SendHistoryReport($owner->tenant_id, $owner->id, '2026-08-01', '2026-08-31'), 'handle']);

        // Assert
        Notification::assertSentTo($owner, HistoryReportNotification::class, function (HistoryReportNotification $notification) use ($owner): bool {
            $attachment = $notification->toMail($owner)->rawAttachments[0];
            $csv = $attachment['data'];
            $lines = explode("\r\n", substr($csv, 3));

            return $attachment['name'] === 'historico-linkcut_2026-08-01_a_2026-08-31.csv'
                && str_starts_with($csv, "\xEF\xBB\xBF")
                && $lines[0] === 'Data;Hora;Cliente;Serviço;"Valor (R$)"'
                && $lines[1] === '11/08/2026;14:30;"João; Silva";Corte;50,00'
                && $lines[2] === '10/08/2026;09:00;"\'=HYPERLINK(""x"")";Corte;65,50'
                && count($lines) === 4
                && ! str_contains($csv, 'outra barbearia');
        });
        Notification::assertNotSentTo($other, HistoryReportNotification::class);
    }

    public function test_a_period_longer_than_one_year_is_refused(): void
    {
        // Arrange
        Queue::fake();
        $this->actingAsBarber();

        // Act
        $response = $this->postJson('/api/finance/history/report', ['from' => '2025-01-01', 'to' => '2026-08-31']);

        // Assert
        $response->assertUnprocessable()->assertJsonPath('errors.to.0', 'period_too_long');
        Queue::assertNothingPushed();
    }

    public function test_the_report_requires_a_session(): void
    {
        // Act
        $response = $this->postJson('/api/finance/history/report', ['from' => '2026-08-01', 'to' => '2026-08-31']);

        // Assert
        $response->assertUnauthorized();
    }
}
