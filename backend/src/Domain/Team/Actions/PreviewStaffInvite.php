<?php

declare(strict_types=1);

namespace Domain\Team\Actions;

use Domain\Team\Data\StaffInvitePreviewData;
use Domain\Team\Exceptions\StaffInviteInvalidException;
use Domain\Team\Models\Staff;
use Domain\Team\Models\StaffInvite;
use Domain\Team\Support\StaffInviteToken;
use Domain\Tenant\Models\Tenant;

final class PreviewStaffInvite
{
    public function handle(string $token): StaffInvitePreviewData
    {
        $invite = $this->findInvite($token);
        $staff = Staff::withoutTenantScope()->whereKey($invite->staff_id)->first();
        if (! $staff) {
            throw new StaffInviteInvalidException;
        }
        $unitName = (string) Tenant::query()->whereKey($staff->tenant_id)->value('name');

        return new StaffInvitePreviewData(
            name: $staff->name,
            email: $invite->email,
            unit_name: $unitName,
            expired: $invite->expires_at->isPast(),
            accepted: $invite->accepted_at !== null,
        );
    }

    private function findInvite(string $token): StaffInvite
    {
        $hash = StaffInviteToken::hash($token);
        $invite = StaffInvite::query()->where('token_hash', $hash)->first();
        if (! $invite || ! StaffInviteToken::matches($token, $invite->token_hash)) {
            throw new StaffInviteInvalidException;
        }

        return $invite;
    }
}
