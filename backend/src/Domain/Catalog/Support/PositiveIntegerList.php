<?php

declare(strict_types=1);

namespace Domain\Catalog\Support;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** Each entry of an id list is an integer of at least 1. */
final class PositiveIntegerList implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_array($value)) {
            return;
        }

        foreach ($value as $item) {
            if (! is_int($item) || $item < 1) {
                $fail('integer');

                return;
            }
        }
    }
}
