<?php

declare(strict_types=1);

namespace Domain\Settings\Support;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** Brazilian phone with area code: 10 or 11 digits, optionally with the country code 55. Formatting is ignored. */
final class WhatsappNumberRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $digits = preg_replace('/\D/', '', (string) $value) ?? '';
        $local = strlen($digits) > 11 && str_starts_with($digits, '55') ? substr($digits, 2) : $digits;

        if (! preg_match('/^\d{10,11}$/', $local)) {
            $fail('phone');
        }
    }
}
