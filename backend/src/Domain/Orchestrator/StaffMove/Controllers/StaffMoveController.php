<?php

declare(strict_types=1);

namespace Domain\Orchestrator\StaffMove\Controllers;

use Domain\Orchestrator\StaffMove\Actions\MoveStaff;
use Domain\Orchestrator\StaffMove\Data\MoveStaffData;
use Domain\Team\Data\StaffIdData;
use Domain\Team\Data\StaffMoveData;

final class StaffMoveController
{
    public function store(int $id, MoveStaffData $data, MoveStaff $action): StaffMoveData
    {
        return $action->handle(new StaffIdData(id: $id), $data);
    }
}
