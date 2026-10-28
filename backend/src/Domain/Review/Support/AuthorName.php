<?php

declare(strict_types=1);

namespace Domain\Review\Support;

use Domain\Notification\Support\PlainText;

/** The first name shown on a public review card, taken from the name on the booking. */
final class AuthorName
{
    public static function first(string $fullName): string
    {
        $clean = PlainText::name($fullName);
        $first = preg_split('/\s+/u', $clean, 2)[0] ?? '';

        return $first !== '' ? $first : 'cliente';
    }
}
