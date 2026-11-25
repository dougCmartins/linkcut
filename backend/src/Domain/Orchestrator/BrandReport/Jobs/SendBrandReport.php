<?php

declare(strict_types=1);

namespace Domain\Orchestrator\BrandReport\Jobs;

use Domain\Finance\Data\PeriodData;
use Domain\Identity\Models\User;
use Domain\Orchestrator\BrandReport\Actions\BuildBrandReport;
use Domain\Orchestrator\BrandReport\Actions\WriteBrandCsv;
use Domain\Orchestrator\BrandReport\Exceptions\BrandReportForbiddenException;
use Domain\Orchestrator\BrandReport\Notifications\BrandReportNotification;
use Domain\Orchestrator\BrandReport\Support\AssertBrandOwner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/** Builds the CSV of the brand and e-mails it to the owner who asked (checked again here: the account may have changed). */
final class SendBrandReport implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public readonly int $organizationId,
        public readonly int $userId,
        public readonly string $from,
        public readonly string $to,
    ) {}

    public function handle(BuildBrandReport $build, WriteBrandCsv $writeCsv): void
    {
        $user = User::query()->find($this->userId);
        if (! $user) {
            return;
        }
        try {
            $organizationId = AssertBrandOwner::handle($user);
        } catch (BrandReportForbiddenException) {
            return;
        }
        if ($organizationId !== $this->organizationId) {
            return;
        }

        $period = new PeriodData(from: $this->from, to: $this->to);
        $csv = $writeCsv->handle($build->handle($organizationId, $period));
        $user->notify(new BrandReportNotification($period, WriteBrandCsv::fileName($period), $csv));
        Log::info('finance_brand_report_sent', ['user_id' => $user->id, 'bytes' => strlen($csv)]);
    }
}
