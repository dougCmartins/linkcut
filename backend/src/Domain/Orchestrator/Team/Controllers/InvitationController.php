<?php

declare(strict_types=1);

namespace Domain\Orchestrator\Team\Controllers;

use Domain\Identity\Data\SessionData;
use Domain\Orchestrator\Team\Actions\AcceptStaffInvitation;
use Domain\Orchestrator\Team\Data\AcceptInvitationData;

final class InvitationController
{
    public function accept(AcceptInvitationData $data, AcceptStaffInvitation $action): SessionData
    {
        return $action->handle($data);
    }
}
