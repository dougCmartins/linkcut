<?php

declare(strict_types=1);

namespace Domain\Team\Actions;

use Domain\Team\Models\Staff;
use Domain\Team\Models\StaffInvitation;
use Domain\Team\Notifications\StaffInvitationNotification;
use Domain\Tenant\Models\Tenant;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

/**
 * A new single-use link for a barber who has no login yet. Sending again cancels the links sent before.
 * Only the hash of the token is stored; the token itself goes only in the e-mail.
 */
final class SendStaffInvitation
{
    public function handle(Staff $staff): void
    {
        $token = Str::random(48);
        StaffInvitation::query()->where('staff_id', $staff->id)->whereNull('accepted_at')->delete();
        StaffInvitation::query()->create([
            'staff_id' => $staff->id,
            'token_hash' => StaffInvitation::hashOf($token),
            'expires_at' => now()->addDays(StaffInvitation::TTL_DAYS),
        ]);

        $barbershop = (string) Tenant::query()->whereKey($staff->tenant_id)->value('name');
        $url = rtrim((string) config('app.frontend_url'), '/').'/equipe/convite?token='.$token;
        Notification::route('mail', $staff->email)->notify(new StaffInvitationNotification($barbershop, $staff->name, $url));
    }
}
