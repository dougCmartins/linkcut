<?php

declare(strict_types=1);

return [
    // "log" writes to storage/logs/sms.log (local/testing only). A real provider comes in a later phase.
    'driver' => env('SMS_DRIVER', 'log'),
];
