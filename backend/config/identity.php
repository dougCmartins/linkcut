<?php

declare(strict_types=1);

return [
    // Second step of sign-in (code by e-mail or SMS).
    'two_factor' => [
        'code_length' => 6,
        'code_ttl_minutes' => (int) env('TWO_FACTOR_CODE_TTL_MINUTES', 5),
        'challenge_ttl_minutes' => (int) env('TWO_FACTOR_CHALLENGE_TTL_MINUTES', 10),
        'max_attempts' => 5,
        'max_sends' => 5,
        'resend_after_seconds' => (int) env('TWO_FACTOR_RESEND_AFTER_SECONDS', 30),
    ],

    // Open sign-up of barbers: the e-mail is confirmed with a code before the account exists.
    'sign_up' => [
        'code_ttl_minutes' => 15,
        'max_attempts' => 5,
        'resend_after_seconds' => 30,
    ],

    // Short access token (Passport) + opaque refresh token rotated on every use.
    'session' => [
        'access_token_ttl_minutes' => (int) env('ACCESS_TOKEN_TTL_MINUTES', 15),
        'refresh_token_ttl_minutes' => (int) env('REFRESH_TOKEN_TTL_MINUTES', 60 * 24 * 7),
    ],
];
