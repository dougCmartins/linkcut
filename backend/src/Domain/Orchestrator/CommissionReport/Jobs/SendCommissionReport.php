<?php

declare(strict_types=1);

namespace Domain\Orchestrator\CommissionReport\Jobs;

use Domain\Finance\Actions\WriteCommissionCsv;
use Domain\Finance\Data\CommissionReportQueryData;
use Domain\Identity\Models\User;
use Domain\Orchestrator\CommissionReport\Notifications\CommissionReportNotification;
use Domain\Tenant\Context\CurrentTenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

final class SendCommissionReport implements ShouldQueue
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
        public readonly ?int $staffId,
    ) {}

    public function handle(CurrentTenant $currentTenant, WriteCommissionCsv $writeCsv): void
    {
        $user = User::query()->find($this->userId);
        if (! $user || $user->tenant_id !== $this->tenantId) {
            return;
        }

        $query = new CommissionReportQueryData(from: $this->from, to: $this->to, staff_id: $this->staffId);
        $csv = $currentTenant->run($this->tenantId, fn (): string => $writeCsv->handle($query));
        $user->notify(new CommissionReportNotification($query, WriteCommissionCsv::fileName($query), $csv));
        Log::info('finance_commission_report_sent', ['user_id' => $user->id, 'bytes' => strlen($csv)]);
    }
}
