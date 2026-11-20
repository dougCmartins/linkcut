<?php

declare(strict_types=1);

namespace Domain\Team\Actions;

use Domain\Team\Data\CreateStaffInviteData;
use Domain\Team\Data\StaffInviteCreatedData;
use Domain\Team\Models\Staff;
use Domain\Team\Models\StaffInvite;
use Domain\Team\Models\StaffPermission;
use Domain\Team\Support\StaffDataMapper;
use Domain\Team\Support\StaffInviteToken;

/** Creates a pending staff member and a one-time invite (plain token returned once). */
final class CreateStaffInvite
{
    public function handle(CreateStaffInviteData $data): StaffInviteCreatedData
    {
        $email = mb_strtolower(trim($data->email));
        $token = StaffInviteToken::generate();
        $ttlDays = max(1, (int) config('team.invite_ttl_days', 7));

        $staff = Staff::query()->create([
            'user_id' => null,
            'name' => trim($data->name),
            'avatar' => null,
            'active' => true,
        ]);
        StaffPermission::query()->create([
            'staff_id' => $staff->id,
            'see_prices' => false,
            'see_own_commission' => false,
            'see_unit_agenda' => false,
        ]);
        StaffInvite::query()->create([
            'staff_id' => $staff->id,
            'email' => $email,
            'token_hash' => StaffInviteToken::hash($token),
            'expires_at' => now()->addDays($ttlDays),
            'accepted_at' => null,
            'created_by' => $data->created_by,
        ]);

        $inviteUrl = rtrim((string) config('app.frontend_url'), '/').'/convite/'.$token;

        return new StaffInviteCreatedData(
            staff: StaffDataMapper::toData($staff->load('permissions')),
            email: $email,
            plain_token: $token,
            invite_url: $inviteUrl,
        );
    }
}
