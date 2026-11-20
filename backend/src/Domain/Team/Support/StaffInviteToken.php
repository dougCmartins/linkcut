<?php

declare(strict_types=1);

namespace Domain\Team\Support;

use Illuminate\Support\Str;

/** Plain invite token (sent by e-mail) and its HMAC (stored). */
final class StaffInviteToken
{
    public static function generate(): string
    {
        return Str::random(64);
    }

    public static function hash(string $token): string
    {
        return hash_hmac('sha256', 'staff-invite|'.$token, (string) config('app.key'));
    }

    public static function matches(string $token, ?string $hash): bool
    {
        return $hash !== null && $hash !== '' && hash_equals($hash, self::hash($token));
    }
}
