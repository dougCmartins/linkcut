<?php

declare(strict_types=1);

namespace App\Sms;

use Illuminate\Support\Facades\Log;

/**
 * Development only: writes the SMS to storage/logs/sms.log instead of sending it.
 * Refused in production (see AppServiceProvider).
 */
final class LogSmsSender implements SmsSender
{
    public function send(string $phone, string $message): void
    {
        Log::channel('sms')->info('sms_sent', ['to' => $phone, 'message' => $message]);
    }
}
