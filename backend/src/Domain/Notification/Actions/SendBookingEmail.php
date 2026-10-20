<?php

declare(strict_types=1);

namespace Domain\Notification\Actions;

use Domain\Notification\Data\BookingEmailData;
use Domain\Notification\Notifications\BookingEmailNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

final class SendBookingEmail
{
    public function handle(BookingEmailData $data): void
    {
        Notification::route('mail', $data->client_email)->notify(new BookingEmailNotification($data));
        Log::info('notification_booking_email_queued', ['kind' => $data->kind->value]);
    }
}
