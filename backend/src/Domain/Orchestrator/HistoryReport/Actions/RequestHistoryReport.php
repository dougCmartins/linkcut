<?php

declare(strict_types=1);

namespace Domain\Orchestrator\HistoryReport\Actions;

use Domain\Finance\Data\PeriodData;
use Domain\Identity\Models\User;
use Domain\Identity\Support\ContactMask;
use Domain\Orchestrator\HistoryReport\Data\HistoryReportRequestedData;
use Domain\Orchestrator\HistoryReport\Jobs\SendHistoryReport;
use Domain\Tenant\Context\CurrentTenant;
use Illuminate\Support\Facades\Log;

/**
 * "Exportar CSV", like a bank statement: the request only queues the report and answers at once; the queue builds
 * the CSV (Finance) and e-mails it to the barber (Identity). The API never holds a request open over a long year.
 */
final class RequestHistoryReport
{
    public function __construct(private readonly CurrentTenant $currentTenant) {}

    public function handle(PeriodData $data): HistoryReportRequestedData
    {
        /** @var User $user */
        $user = auth('api')->user();
        SendHistoryReport::dispatch($this->currentTenant->id(), $user->id, $data->from, $data->to);
        Log::info('finance_history_report_requested', ['from' => $data->from, 'to' => $data->to]);

        return new HistoryReportRequestedData(email: ContactMask::email($user->email));
    }
}
