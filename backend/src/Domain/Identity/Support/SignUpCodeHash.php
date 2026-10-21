<?php

declare(strict_types=1);

namespace Domain\Identity\Support;

/** HMAC of a sign-up code, bound to the e-mail it was sent to. */
final class SignUpCodeHash
{
    public static function make(string $email, string $code): string
    {
        return hash_hmac('sha256', "sign-up|{$email}|{$code}", (string) config('app.key'));
    }
}
