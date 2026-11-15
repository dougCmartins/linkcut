<?php

declare(strict_types=1);

namespace Domain\Finance\Support;

use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * The history and its report cover at most one year at a time ("to" up to 366 days after "from"), so a single request
 * never scans the whole table. Longer periods are asked year by year.
 */
final class PeriodWithinOneYear implements DataAwareRule, ValidationRule
{
    public const MAX_DAYS = 366;

    /** @var array<string, mixed> */
    private array $data = [];

    /** @param array<string, mixed> $data */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $from = $this->data['from'] ?? null;
        if (! is_string($from) || ! is_string($value)) {
            return;
        }
        $start = CarbonImmutable::createFromFormat('!Y-m-d', $from);
        $end = CarbonImmutable::createFromFormat('!Y-m-d', $value);
        if ($start && $end && $start->diffInDays($end) > self::MAX_DAYS) {
            $fail('period_too_long');
        }
    }
}
