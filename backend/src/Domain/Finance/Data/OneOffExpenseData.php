<?php

declare(strict_types=1);

namespace Domain\Finance\Data;

use Spatie\LaravelData\Data;

final class OneOffExpenseData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly string $description,
        public readonly int $amount_in_cents,
        public readonly string $date,
    ) {}
}
