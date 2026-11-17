<?php

declare(strict_types=1);

namespace Domain\Orchestrator\HistoryReport\Jobs;

use Domain\Finance\Actions\WriteHistoryCsv;
use Domain\Finance\Data\PeriodData;
use Domain\Identity\Models\User;
use Domain\Orchestrator\HistoryReport\Notifications\HistoryReportNotification;
use Domain\Tenant\Actions\ResolveActiveUnit;
use Domain\Tenant\Context\CurrentTenant;
use Domain\Tenant\Data\UnitAccessData;
use DomainException;
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

    public function handle(CurrentTenant $currentTenant, WriteHistoryCsv $writeCsv, ResolveActiveUnit $resolveUnit): void
    {
        $user = User::query()->find($this->userId);
        // The account may have been deleted meanwhile, or lost access to the unit: nothing to send.
        if (! $user || ! $this->canOpenUnit($user, $resolveUnit)) {
            return;
        }

        $period = new PeriodData(from: $this->from, to: $this->to);
        $csv = $currentTenant->run($this->tenantId, fn (): string => $writeCsv->handle($period));
        $user->notify(new HistoryReportNotification($period, WriteHistoryCsv::fileName($period), $csv));
        Log::info('finance_history_report_sent', ['user_id' => $user->id, 'bytes' => strlen($csv)]);
    }

    /** The same check as the dashboard: the user's own unit, or a unit of the organization they own. */
    private function canOpenUnit(User $user, ResolveActiveUnit $resolveUnit): bool
    {
        try {
            $resolveUnit->handle(new UnitAccessData(
                user_id: $user->id,
                home_tenant_id: $user->tenant_id,
                organization_id: $user->organization_id,
                unit_id: $this->tenantId,
            ));

            return true;
        } catch (DomainException) {
            return false;
        }
    }
}
