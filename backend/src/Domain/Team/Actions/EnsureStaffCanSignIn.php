<?php

declare(strict_types=1);

namespace Domain\Team\Actions;

use Domain\Team\Exceptions\StaffInactiveException;
use Domain\Team\Models\Staff;

/** A barber the owner turned off cannot sign in again (their sessions were already ended when turned off). */
final class EnsureStaffCanSignIn
{
    public function handle(int $userId): void
    {
        $isTurnedOff = Staff::withoutTenantScope()->where('user_id', $userId)->where('active', false)->exists();
        if ($isTurnedOff) {
            throw new StaffInactiveException;
        }
    }
}
