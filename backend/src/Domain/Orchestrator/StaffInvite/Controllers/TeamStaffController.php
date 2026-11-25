<?php

declare(strict_types=1);

namespace Domain\Orchestrator\StaffInvite\Controllers;

use Domain\Orchestrator\StaffInvite\Actions\InviteStaff;
use Domain\Orchestrator\StaffInvite\Actions\PresentStaffList;
use Domain\Orchestrator\StaffInvite\Actions\UpdateTeamStaffActive;
use Domain\Orchestrator\StaffInvite\Actions\UpdateTeamStaffPermissions;
use Domain\Team\Data\InviteStaffData;
use Domain\Team\Data\StaffData;
use Domain\Team\Data\StaffIdData;
use Domain\Team\Data\StaffPermissionsData;
use Domain\Team\Data\UpdateStaffActiveData;
use Illuminate\Http\JsonResponse;
use Spatie\LaravelData\DataCollection;

final class TeamStaffController
{
    /** @return DataCollection<int, StaffData> */
    public function index(PresentStaffList $action): DataCollection
    {
        return $action->handle();
    }

    public function store(InviteStaffData $data, InviteStaff $action): JsonResponse
    {
        return response()->json($action->handle($data)->toArray(), 201);
    }

    public function updateActive(int $id, UpdateStaffActiveData $data, UpdateTeamStaffActive $action): StaffData
    {
        return $action->handle(self::id($id), $data);
    }

    public function updatePermissions(int $id, StaffPermissionsData $data, UpdateTeamStaffPermissions $action): StaffData
    {
        return $action->handle(self::id($id), $data);
    }

    private static function id(int $id): StaffIdData
    {
        return StaffIdData::from(StaffIdData::validate(['id' => $id]));
    }
}
