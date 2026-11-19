<?php

declare(strict_types=1);

namespace Domain\Orchestrator\Team\Controllers;

use Domain\Orchestrator\Team\Actions\UpdateTeamMember;
use Domain\Team\Data\StaffActorData;
use Domain\Team\Data\StaffData;
use Domain\Team\Data\StaffUpdateData;
use Illuminate\Http\Request;

final class TeamMemberController
{
    public function update(Request $request, int $id, StaffUpdateData $data, UpdateTeamMember $action): StaffData
    {
        return $action->handle(new StaffActorData(user_id: (int) $request->user()->getAuthIdentifier(), staff_id: $id), $data);
    }
}
