<?php

declare(strict_types=1);

namespace Domain\Finance\Data;

use Spatie\LaravelData\Data;

final class FixedExpenseData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly int $amount_in_cents,
        public readonly int $due_day,
    ) {}
}
