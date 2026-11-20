<?php

declare(strict_types=1);

namespace Domain\Orchestrator\Team\Actions;

use Domain\Identity\Actions\RevokeUserSessions;
use Domain\Identity\Data\UserIdData;
use Domain\Team\Actions\ShowStaffUserId;
use Domain\Team\Actions\UpdateStaff;
use Domain\Team\Data\StaffActorData;
use Domain\Team\Data\StaffData;
use Domain\Team\Data\StaffUpdateData;

/**
 * The owner changes a barber (Team); turning one off also signs them out of every device at once (Identity), so
 * nothing they had open keeps working. Turning them on again only lets them sign in again.
 */
final class UpdateTeamMember
{
    public function __construct(
        private readonly UpdateStaff $updateStaff,
        private readonly ShowStaffUserId $showStaffUserId,
        private readonly RevokeUserSessions $revokeSessions,
    ) {}

    public function handle(StaffActorData $actor, StaffUpdateData $data): StaffData
    {
        $staff = $this->updateStaff->handle($actor, $data);
        $userId = $this->showStaffUserId->handle($staff->id);
        if (! $data->active && $userId !== null) {
            $this->revokeSessions->handle(new UserIdData(user_id: $userId));
        }

        return $staff;
    }
}
