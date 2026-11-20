<?php

declare(strict_types=1);

namespace Domain\Notification\Actions;

use Domain\Notification\Data\StaffInviteMailData;
use Domain\Notification\Notifications\StaffInviteNotification;
use Illuminate\Support\Facades\Notification;

final class SendStaffInvite
{
    public function handle(StaffInviteMailData $data): void
    {
        Notification::route('mail', $data->barber_email)->notify(new StaffInviteNotification($data));
    }
}
