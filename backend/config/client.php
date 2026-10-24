<?php

declare(strict_types=1);

return [
    // Codes sent by e-mail (complete the sign-up, new password).
    'code' => [
        'length' => 6,
        'ttl_minutes' => (int) env('CLIENT_CODE_TTL_MINUTES', 15),
        'max_attempts' => 5,
        // Wrong codes per account per day, across resends (a new code does not give new guesses).
        'daily_max_failures' => 10,
        'resend_after_seconds' => (int) env('CLIENT_CODE_RESEND_AFTER_SECONDS', 30),
    ],

    // Clients come back now and then to see their appointments: a longer session than the barbers'.
    'session' => [
        'access_token_ttl_minutes' => (int) env('CLIENT_ACCESS_TOKEN_TTL_MINUTES', 60),
        'refresh_token_ttl_minutes' => (int) env('CLIENT_REFRESH_TOKEN_TTL_MINUTES', 60 * 24 * 30),
    ],

    // The single "complete your sign-up" e-mail goes to whoever booked at least this long ago.
    'invite_after_hours' => 2,

    // The client cancels from the client area until this long before the time (after that, only by WhatsApp).
    'cancel_until_hours' => (int) env('CLIENT_CANCEL_UNTIL_HOURS', 2),
];
