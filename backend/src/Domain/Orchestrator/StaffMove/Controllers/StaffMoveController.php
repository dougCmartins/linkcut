<?php

declare(strict_types=1);

namespace Domain\Orchestrator\StaffMove\Controllers;

use Domain\Orchestrator\StaffMove\Actions\MoveStaff;
use Domain\Orchestrator\StaffMove\Actions\ShowMoveOptions;
use Domain\Orchestrator\StaffMove\Data\BrandMoveData;
use Domain\Orchestrator\StaffMove\Data\MoveOptionsData;
use Domain\Orchestrator\StaffMove\Data\MoveOptionsQueryData;
use Domain\Orchestrator\StaffMove\Data\MoveStaffData;
use Domain\Team\Data\StaffIdData;
use Domain\Team\Data\StaffMoveData;

final class StaffMoveController
{
    public function options(int $id, MoveOptionsQueryData $query, ShowMoveOptions $action): MoveOptionsData
    {
        return $action->options(new StaffIdData(id: $id), $query->to_unit_id);
    }

    /** @return list<BrandMoveData> */
    public function index(ShowMoveOptions $action): array
    {
        return $action->moves();
    }

    public function store(int $id, MoveStaffData $data, MoveStaff $action): StaffMoveData
    {
        return $action->handle(new StaffIdData(id: $id), $data);
    }
}
