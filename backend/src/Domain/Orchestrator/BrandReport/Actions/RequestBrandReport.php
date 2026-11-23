<?php

declare(strict_types=1);

namespace Domain\Orchestrator\BrandReport\Actions;

use Domain\Finance\Data\PeriodData;
use Domain\Identity\Models\User;
use Domain\Identity\Support\ContactMask;
use Domain\Orchestrator\BrandReport\Data\BrandReportRequestedData;
use Domain\Orchestrator\BrandReport\Jobs\SendBrandReport;
use Domain\Orchestrator\BrandReport\Support\AssertBrandOwner;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/** Queues the CSV of the brand and answers at once, like the history report. */
final class RequestBrandReport
{
    public function handle(PeriodData $data): BrandReportRequestedData
    {
        /** @var User|null $user */
        $user = Auth::user();
        $organizationId = AssertBrandOwner::handle($user);
        SendBrandReport::dispatch($organizationId, $user->id, $data->from, $data->to);
        Log::info('finance_brand_report_requested', ['organization_id' => $organizationId, 'from' => $data->from, 'to' => $data->to]);

        return new BrandReportRequestedData(email: ContactMask::email($user->email));
    }
}
