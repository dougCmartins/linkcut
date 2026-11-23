<?php

declare(strict_types=1);

namespace Domain\Team\Actions;

use Domain\Team\Data\ConsumeStaffInviteResultData;
use Domain\Team\Exceptions\StaffInviteInvalidException;
use Domain\Team\Models\Staff;
use Domain\Team\Models\StaffInvite;
use Domain\Team\Support\StaffInviteToken;
use Illuminate\Support\Facades\DB;

/** Validates a one-time invite and marks it accepted inside a transaction. */
final class ConsumeStaffInvite
{
    public function handle(string $token): ConsumeStaffInviteResultData
    {
        return DB::transaction(function () use ($token): ConsumeStaffInviteResultData {
            $hash = StaffInviteToken::hash($token);
            $invite = StaffInvite::query()->where('token_hash', $hash)->lockForUpdate()->first();
            if (! $invite || ! StaffInviteToken::matches($token, $invite->token_hash)) {
                throw new StaffInviteInvalidException;
            }
            if ($invite->accepted_at !== null || $invite->expires_at->isPast()) {
                throw new StaffInviteInvalidException;
            }

            // Public accept has no CurrentTenant; Staff is tenant-scoped.
            $staff = Staff::withoutTenantScope()->whereKey($invite->staff_id)->lockForUpdate()->first();
            if (! $staff || $staff->user_id !== null) {
                throw new StaffInviteInvalidException;
            }

            $invite->update(['accepted_at' => now()]);

            return new ConsumeStaffInviteResultData(
                invite_id: $invite->id,
                staff_id: $staff->id,
                tenant_id: (int) $staff->tenant_id,
                email: $invite->email,
                staff_name: $staff->name,
            );
        });
    }
}
