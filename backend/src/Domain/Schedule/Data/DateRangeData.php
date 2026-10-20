<?php

declare(strict_types=1);

namespace Domain\Schedule\Data;

use Spatie\LaravelData\Data;

/** Inclusive range of dates "Y-m-d". */
final class DateRangeData extends Data
{
    public function __construct(
        public readonly string $from,
        public readonly string $to,
    ) {}
}
