<?php

declare(strict_types=1);

namespace Domain\Identity\Support;

/** Generates 2FA codes and their HMAC. The plain code only lives in memory and in the message sent. */
final class TwoFactorCode
{
    public static function generate(): string
    {
        $length = (int) config('identity.two_factor.code_length');

        return str_pad((string) random_int(0, 10 ** $length - 1), $length, '0', STR_PAD_LEFT);
    }

    /** Bound to the challenge, so a code hash is useless for any other challenge. */
    public static function hash(string $challengeId, string $code): string
    {
        return hash_hmac('sha256', $challengeId.'|'.$code, (string) config('app.key'));
    }

    public static function matches(string $challengeId, string $code, ?string $hash): bool
    {
        return $hash && hash_equals($hash, self::hash($challengeId, $code));
    }
}
