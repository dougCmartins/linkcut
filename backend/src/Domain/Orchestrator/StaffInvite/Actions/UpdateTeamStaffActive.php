<?php

declare(strict_types=1);

namespace Domain\Orchestrator\StaffInvite\Actions;

use Domain\Identity\Enums\UserRole;
use Domain\Identity\Models\User;
use Domain\Team\Actions\UpdateStaffActive;
use Domain\Team\Data\StaffData;
use Domain\Team\Data\StaffIdData;
use Domain\Team\Data\UpdateStaffActiveData;
use Domain\Team\Exceptions\OwnerRequiredException;
use Illuminate\Support\Facades\Auth;

final class UpdateTeamStaffActive
{
    public function __construct(private readonly UpdateStaffActive $updateStaffActive) {}

    public function handle(StaffIdData $id, UpdateStaffActiveData $data): StaffData
    {
        /** @var User $user */
        $user = Auth::user();
        if ($user->role !== UserRole::Owner) {
            throw new OwnerRequiredException;
        }

        return $this->updateStaffActive->handle($id, $data, $user->id);
    }
}
