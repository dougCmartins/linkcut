<?php

declare(strict_types=1);

namespace Domain\Client\Support;

/** Where a Google sign-in may send the client back, and the short-lived cache keys around it. */
final class ClientGoogleLogin
{
    public const HOME = '/conta';

    /** Long enough to leave for Google and come back. */
    public const STATE_TTL_SECONDS = 600;

    /** The browser exchanges this once; then it is gone. */
    public const CODE_TTL_SECONDS = 60;

    /**
     * Same rule as the SPA (`safeClientRedirect`): only an in-app path. Anything else, including
     * `https://…`, `//…` and backslashes, goes to the client home.
     */
    public static function safeReturnPath(?string $target): string
    {
        if (
            ! is_string($target)
            || ! str_starts_with($target, '/')
            || str_starts_with($target, '//')
            || str_contains($target, '\\')
            || preg_match('/[\x00-\x1F]/', $target) === 1
        ) {
            return self::HOME;
        }

        return $target;
    }

    public static function stateKey(string $state): string
    {
        return 'client-google-state:'.hash('sha256', $state);
    }

    public static function nonceHash(string $nonce): string
    {
        return hash('sha256', $nonce);
    }

    public static function codeKey(string $code): string
    {
        return 'client-google-code:'.hash('sha256', $code);
    }
}
