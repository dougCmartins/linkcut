<?php

declare(strict_types=1);

namespace Domain\Orchestrator\StaffMove\Controllers;

use Domain\Orchestrator\StaffMove\Actions\ManageLoan;
use Domain\Orchestrator\StaffMove\Data\ExtendLoanData;
use Domain\Team\Data\StaffMoveData;

final class LoanController
{
    public function extend(int $id, ExtendLoanData $data, ManageLoan $action): StaffMoveData
    {
        return $action->extend($id, $data->ends_on);
    }

    public function end(int $id, ManageLoan $action): StaffMoveData
    {
        return $action->end($id);
    }

    public function makePermanent(int $id, ManageLoan $action): StaffMoveData
    {
        return $action->makePermanent($id);
    }
}
