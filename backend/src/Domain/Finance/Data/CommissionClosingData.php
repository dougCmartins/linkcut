<?php

declare(strict_types=1);

namespace Domain\Finance\Data;

use Spatie\LaravelData\Data;

final class CommissionClosingData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly string $from,
        public readonly string $to,
        public readonly string $closed_at,
        public readonly int $closed_by,
        public readonly ?string $reopened_at,
    ) {}
}
