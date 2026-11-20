<?php

declare(strict_types=1);

namespace Domain\Orchestrator\StaffInvite\Actions;

use Domain\Identity\Enums\UserRole;
use Domain\Identity\Models\User;
use Domain\Team\Actions\ListStaff;
use Domain\Team\Data\StaffData;
use Domain\Team\Exceptions\OwnerRequiredException;
use Illuminate\Support\Facades\Auth;
use Spatie\LaravelData\DataCollection;

final class PresentStaffList
{
    public function __construct(private readonly ListStaff $listStaff) {}

    /** @return DataCollection<int, StaffData> */
    public function handle(): DataCollection
    {
        /** @var User $user */
        $user = Auth::user();
        if ($user->role === UserRole::Owner) {
            return $this->listStaff->handle();
        }
        if ($user->role === UserRole::Barber) {
            return $this->listStaff->handle($user->id);
        }

        throw new OwnerRequiredException;
    }
}
