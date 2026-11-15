<?php

declare(strict_types=1);

namespace Domain\Orchestrator\HistoryReport\Jobs;

use Domain\Finance\Actions\WriteHistoryCsv;
use Domain\Finance\Data\PeriodData;
use Domain\Identity\Models\User;
use Domain\Orchestrator\HistoryReport\Notifications\HistoryReportNotification;
use Domain\Tenant\Context\CurrentTenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/** Builds the CSV of the period inside the barbershop's tenant and e-mails it to the barber who asked. */
final class SendHistoryReport implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public readonly int $tenantId,
        public readonly int $userId,
        public readonly string $from,
        public readonly string $to,
    ) {}

    public function handle(CurrentTenant $currentTenant, WriteHistoryCsv $writeCsv): void
    {
        $user = User::query()->find($this->userId);
        // The account may have been deleted meanwhile: nothing to send.
        if (! $user || $user->tenant_id !== $this->tenantId) {
            return;
        }

        $period = new PeriodData(from: $this->from, to: $this->to);
        $csv = $currentTenant->run($this->tenantId, fn (): string => $writeCsv->handle($period));
        $user->notify(new HistoryReportNotification($period, WriteHistoryCsv::fileName($period), $csv));
        Log::info('finance_history_report_sent', ['user_id' => $user->id, 'bytes' => strlen($csv)]);
    }
}
