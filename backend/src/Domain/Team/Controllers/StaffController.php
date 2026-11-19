<?php

declare(strict_types=1);

namespace Domain\Team\Controllers;

use Domain\Team\Actions\InviteStaff;
use Domain\Team\Actions\ListStaff;
use Domain\Team\Actions\ResendStaffInvitation;
use Domain\Team\Data\NewStaffData;
use Domain\Team\Data\StaffActorData;
use Domain\Team\Data\StaffData;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class StaffController
{
    /** @return list<StaffData> */
    public function index(Request $request, ListStaff $action): array
    {
        return $action->handle(new StaffActorData(user_id: (int) $request->user()->getAuthIdentifier()));
    }

    public function store(Request $request, NewStaffData $data, InviteStaff $action): StaffData
    {
        return $action->handle(new StaffActorData(user_id: (int) $request->user()->getAuthIdentifier()), $data);
    }

    public function resendInvitation(Request $request, int $id, ResendStaffInvitation $action): Response
    {
        $action->handle(new StaffActorData(user_id: (int) $request->user()->getAuthIdentifier(), staff_id: $id));

        return response()->noContent();
    }
}
