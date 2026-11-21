<?php

declare(strict_types=1);

namespace Domain\Orchestrator\CommissionReport\Actions;

use Domain\Finance\Actions\ListCommissionReport;
use Domain\Finance\Data\CommissionReportData;
use Domain\Finance\Data\CommissionReportQueryData;
use Domain\Finance\Exceptions\CommissionForbiddenException;
use Domain\Identity\Enums\UserRole;
use Domain\Identity\Models\User;
use Domain\Team\Actions\FindStaffByUser;
use Domain\Team\Actions\FindStaffPermissionsForUser;
use Illuminate\Support\Facades\Auth;

/** Owner sees any staff; barber sees own entries only when see_own_commission is on. */
final class PresentCommissionReport
{
    public function __construct(
        private readonly ListCommissionReport $list,
        private readonly FindStaffByUser $findStaff,
        private readonly FindStaffPermissionsForUser $findPermissions,
    ) {}

    public function handle(CommissionReportQueryData $query): CommissionReportData
    {
        /** @var User|null $user */
        $user = Auth::user();
        if ($user === null) {
            throw new CommissionForbiddenException;
        }

        if ($user->role === UserRole::Owner) {
            return $this->list->handle($query);
        }

        $permissions = $this->findPermissions->handle($user->id);
        if ($permissions === null || ! $permissions->see_own_commission) {
            throw new CommissionForbiddenException;
        }

        $staff = $this->findStaff->handle($user->id);
        if ($staff === null) {
            throw new CommissionForbiddenException;
        }

        if ($query->staff_id !== null && $query->staff_id !== $staff->id) {
            throw new CommissionForbiddenException;
        }

        return $this->list->handle(new CommissionReportQueryData(
            from: $query->from,
            to: $query->to,
            staff_id: $staff->id,
        ));
    }
}
