<?php

declare(strict_types=1);

namespace Domain\Finance\Data;

use Spatie\LaravelData\Data;

final class CommissionAdjustmentData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly ?int $staff_id,
        public readonly string $name,
        public readonly string $stage,
        public readonly string $kind,
        public readonly string $applies_to,
        public readonly int $value,
        public readonly bool $active,
        public readonly string $starts_on,
        public readonly ?string $system_key,
    ) {}
}
