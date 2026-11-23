<?php

declare(strict_types=1);

namespace Domain\Orchestrator\CommissionReport\Actions;

use Domain\Finance\Data\CommissionReportQueryData;
use Domain\Finance\Exceptions\CommissionForbiddenException;
use Domain\Identity\Enums\UserRole;
use Domain\Identity\Models\User;
use Domain\Identity\Support\ContactMask;
use Domain\Orchestrator\CommissionReport\Data\CommissionReportRequestedData;
use Domain\Orchestrator\CommissionReport\Jobs\SendCommissionReport;
use Domain\Team\Actions\FindStaffByUser;
use Domain\Team\Actions\FindStaffPermissionsForUser;
use Domain\Tenant\Context\CurrentTenant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

final class RequestCommissionReport
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
        private readonly FindStaffByUser $findStaff,
        private readonly FindStaffPermissionsForUser $findPermissions,
    ) {}

    public function handle(CommissionReportQueryData $data): CommissionReportRequestedData
    {
        /** @var User|null $user */
        $user = Auth::user();
        if ($user === null) {
            throw new CommissionForbiddenException;
        }

        $staffId = $data->staff_id;
        if ($user->role === UserRole::Barber) {
            $permissions = $this->findPermissions->handle($user->id);
            if ($permissions === null || ! $permissions->see_own_commission) {
                throw new CommissionForbiddenException;
            }
            $staff = $this->findStaff->handle($user->id);
            if ($staff === null) {
                throw new CommissionForbiddenException;
            }
            $staffId = $staff->id;
        } elseif ($user->role !== UserRole::Owner) {
            throw new CommissionForbiddenException;
        }

        SendCommissionReport::dispatch(
            $this->currentTenant->id(),
            $user->id,
            $data->from,
            $data->to,
            $staffId,
        );
        Log::info('finance_commission_report_requested', ['from' => $data->from, 'to' => $data->to]);

        return new CommissionReportRequestedData(email: ContactMask::email($user->email));
    }
}
