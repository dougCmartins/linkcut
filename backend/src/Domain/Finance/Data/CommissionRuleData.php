<?php

declare(strict_types=1);

namespace Domain\Finance\Data;

use Spatie\LaravelData\Data;

final class CommissionRuleData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly int $service_id,
        public readonly ?int $staff_id,
        public readonly int $percent,
        public readonly string $starts_on,
    ) {}
}
