<?php

declare(strict_types=1);

namespace Domain\Client\Support;

use Domain\Client\Enums\CodePurpose;

/** 6-digit codes and their HMAC (bound to the client and the purpose). The plain code only goes in the e-mail. */
final class ClientCode
{
    public static function generate(): string
    {
        $length = (int) config('client.code.length');

        return str_pad((string) random_int(0, 10 ** $length - 1), $length, '0', STR_PAD_LEFT);
    }

    public static function hash(int $clientId, CodePurpose $purpose, string $code): string
    {
        return hash_hmac('sha256', "client|{$clientId}|{$purpose->value}|{$code}", (string) config('app.key'));
    }
}
