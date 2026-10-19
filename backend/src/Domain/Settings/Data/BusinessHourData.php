<?php

declare(strict_types=1);

namespace Domain\Settings\Data;

use Spatie\LaravelData\Attributes\Validation\Between;
use Spatie\LaravelData\Attributes\Validation\BooleanType;
use Spatie\LaravelData\Attributes\Validation\DateFormat;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

/** Opening hours of a weekday (0 = Sunday … 6 = Saturday), times "HH:mm". */
final class BusinessHourData extends Data
{
    public function __construct(
        #[Required, IntegerType, Between(0, 6)]
        public readonly int $weekday,
        #[Required, BooleanType]
        public readonly bool $is_open,
        #[Required, DateFormat('H:i')]
        public readonly string $opens_at,
        #[Required, DateFormat('H:i')]
        public readonly string $closes_at,
    ) {}
}
