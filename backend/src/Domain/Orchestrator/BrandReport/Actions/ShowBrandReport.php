<?php

declare(strict_types=1);

namespace Domain\Orchestrator\BrandReport\Actions;

use Domain\Finance\Data\PeriodData;
use Domain\Identity\Models\User;
use Domain\Orchestrator\BrandReport\Data\BrandReportData;
use Domain\Orchestrator\BrandReport\Support\AssertBrandOwner;
use Illuminate\Support\Facades\Auth;

final class ShowBrandReport
{
    public function __construct(private readonly BuildBrandReport $build) {}

    public function handle(PeriodData $period): BrandReportData
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $this->build->handle(AssertBrandOwner::handle($user), $period);
    }
}
