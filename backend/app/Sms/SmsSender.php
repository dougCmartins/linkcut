<?php

declare(strict_types=1);

namespace App\Sms;

/** Sends one SMS. The provider (Twilio, Zenvia, SNS…) plugs in behind this interface. */
interface SmsSender
{
    public function send(string $phone, string $message): void;
}
