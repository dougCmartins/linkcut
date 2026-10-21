<?php

declare(strict_types=1);

return [
    // Off by default (no budget for SMS yet): the 2FA code goes by e-mail only. When on, users with a phone
    // can also get it by SMS; production then needs a real driver.
    'enabled' => (bool) env('SMS_ENABLED', false),

    // "log" writes to storage/logs/sms.log (local/testing only). A real provider comes in a later phase.
    'driver' => env('SMS_DRIVER', 'log'),
];
