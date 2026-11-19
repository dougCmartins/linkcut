<?php

declare(strict_types=1);

namespace Domain\Team\Actions;

use Domain\Team\Data\AcceptedInvitationData;
use Domain\Team\Data\InvitationTokenData;
use Domain\Team\Exceptions\InvitationInvalidException;
use Domain\Team\Models\Staff;
use Domain\Team\Models\StaffInvitation;

/**
 * Uses an invitation link once (call inside a transaction): unknown, used, expired, or for a barber turned off or who
 * already has a login, it is refused the same way. Runs before any tenant is known (a public link).
 */
final class ConsumeStaffInvitation
{
    public function handle(InvitationTokenData $data): AcceptedInvitationData
    {
        $invitation = StaffInvitation::withoutTenantScope()
            ->where('token_hash', StaffInvitation::hashOf($data->token))
            ->lockForUpdate()
            ->first();
        $staff = $invitation ? Staff::withoutTenantScope()->find($invitation->staff_id) : null;
        $isUsable = $invitation !== null
            && $invitation->accepted_at === null
            && $invitation->expires_at->isFuture()
            && $staff !== null
            && $staff->active
            && $staff->user_id === null;
        if (! $isUsable) {
            throw new InvitationInvalidException;
        }

        StaffInvitation::withoutTenantScope()->whereKey($invitation->id)->update(['accepted_at' => now()]);

        return new AcceptedInvitationData(tenant_id: $staff->tenant_id, staff_id: $staff->id, name: $staff->name, email: $staff->email);
    }
}
