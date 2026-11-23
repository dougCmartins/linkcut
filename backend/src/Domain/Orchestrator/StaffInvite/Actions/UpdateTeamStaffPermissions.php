<?php

declare(strict_types=1);

namespace Domain\Orchestrator\StaffInvite\Actions;

use Domain\Identity\Enums\UserRole;
use Domain\Identity\Models\User;
use Domain\Team\Actions\UpdateStaffPermissions;
use Domain\Team\Data\StaffData;
use Domain\Team\Data\StaffIdData;
use Domain\Team\Data\StaffPermissionsData;
use Domain\Team\Exceptions\OwnerRequiredException;
use Illuminate\Support\Facades\Auth;

final class UpdateTeamStaffPermissions
{
    public function __construct(private readonly UpdateStaffPermissions $updateStaffPermissions) {}

    public function handle(StaffIdData $id, StaffPermissionsData $data): StaffData
    {
        /** @var User $user */
        $user = Auth::user();
        if ($user->role !== UserRole::Owner) {
            throw new OwnerRequiredException;
        }

        return $this->updateStaffPermissions->handle($id, $data);
    }
}
