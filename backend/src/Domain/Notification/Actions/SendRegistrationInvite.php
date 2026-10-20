<?php

declare(strict_types=1);

namespace Domain\Notification\Actions;

use Domain\Notification\Data\RegistrationInviteData;
use Domain\Notification\Notifications\RegistrationInviteNotification;
use Illuminate\Support\Facades\Notification;

final class SendRegistrationInvite
{
    public function handle(RegistrationInviteData $data): void
    {
        Notification::route('mail', $data->client_email)->notify(new RegistrationInviteNotification($data));
    }
}
