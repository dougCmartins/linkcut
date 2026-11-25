<?php

declare(strict_types=1);

namespace Domain\Finance\Data;

use Spatie\LaravelData\Attributes\Validation\BooleanType;
use Spatie\LaravelData\Attributes\Validation\DateFormat;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

final class CommissionAdjustmentInputData extends Data
{
    public function __construct(
        #[Nullable, IntegerType, Min(1)]
        public readonly ?int $staff_id,
        #[Required, StringType, Max(60)]
        public readonly string $name,
        #[Required, StringType, In(['before', 'after'])]
        public readonly string $stage,
        #[Required, StringType, In(['percent', 'fixed'])]
        public readonly string $kind,
        #[Required, StringType, In(['price', 'commission', 'appointment'])]
        public readonly string $applies_to,
        #[Required, IntegerType, Min(0)]
        public readonly int $value,
        #[Required, BooleanType]
        public readonly bool $active,
        #[Required, DateFormat('Y-m-d')]
        public readonly string $starts_on,
    ) {}
}
