<?php

declare(strict_types=1);

namespace Domain\Client\Support;

/** Brazilian phones as stored: digits only, without the country code (same as the booking and the barber). */
final class PhoneNumber
{
    public static function digits(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone) ?? '';

        return strlen($digits) > 11 && str_starts_with($digits, '55') ? substr($digits, 2) : $digits;
    }

    public static function isValid(string $digits): bool
    {
        return (bool) preg_match('/^\d{10,11}$/', $digits);
    }
}
