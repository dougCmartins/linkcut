<?php

declare(strict_types=1);

namespace Domain\Identity\Support;

/** Hides most of an e-mail or phone so it can be shown (and logged) safely. */
final class ContactMask
{
    /** rafael@linkcut.app -> ra****@linkcut.app */
    public static function email(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');
        $visible = mb_substr($local, 0, min(2, max(1, mb_strlen($local) - 1)));

        return $visible.str_repeat('*', max(1, mb_strlen($local) - mb_strlen($visible))).'@'.$domain;
    }

    /** +5511988887777 -> +55 •• •••••-7777 */
    public static function phone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone) ?? '';

        return '+'.mb_substr($digits, 0, 2).' •• •••••-'.mb_substr($digits, -4);
    }
}
