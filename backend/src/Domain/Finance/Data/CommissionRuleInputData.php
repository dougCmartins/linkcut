<?php

declare(strict_types=1);

namespace Domain\Finance\Data;

use Spatie\LaravelData\Attributes\Validation\Between;
use Spatie\LaravelData\Attributes\Validation\DateFormat;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

final class CommissionRuleInputData extends Data
{
    public function __construct(
        #[Required, IntegerType, Min(1)]
        public readonly int $service_id,
        #[Nullable, IntegerType, Min(1)]
        public readonly ?int $staff_id,
        /** Tenths of a percent: 0…1000 (0%…100%). */
        #[Required, IntegerType, Between(0, 1000)]
        public readonly int $percent,
        #[Required, DateFormat('Y-m-d')]
        public readonly string $starts_on,
    ) {}
}
