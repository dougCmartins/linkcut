<?php

declare(strict_types=1);

namespace Domain\Notification\Support;

use Illuminate\Support\Str;

/**
 * Text typed by visitors (e.g. the name on a booking) goes into e-mails sent from our domain. It must stay plain
 * text: no links, no Markdown, no HTML, so nobody can use a booking to send phishing in our name.
 */
final class PlainText
{
    private const NAME_MAX = 60;

    public static function name(string $value): string
    {
        $text = preg_replace('~(https?://|www\.)\S*~iu', '', $value) ?? '';
        $text = preg_replace('/[\[\]()<>*_`#|\\\\{}~]/u', '', $text) ?? '';
        $text = preg_replace('~\b\S+\.(com|net|org|br|io|app|xyz|me|info|link)\b\S*~iu', '', $text) ?? '';
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? '');

        return Str::limit($text !== '' ? $text : __('client_mail.default_name'), self::NAME_MAX, '');
    }
}
