<?php

declare(strict_types=1);

namespace Domain\Team\Actions;

use Domain\Team\Data\StaffActorData;
use Domain\Team\Exceptions\StaffNotFoundException;
use Domain\Team\Models\Staff;

/** "Reenviar convite": only for an active barber who has not accepted yet. */
final class ResendStaffInvitation
{
    public function __construct(private readonly SendStaffInvitation $sendInvitation) {}

    public function handle(StaffActorData $actor): void
    {
        $staff = Staff::query()->whereKey($actor->staff_id)->where('active', true)->whereNull('user_id')->first();
        if (! $staff) {
            throw new StaffNotFoundException;
        }
        $this->sendInvitation->handle($staff);
    }
}
