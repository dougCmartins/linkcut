<?php

declare(strict_types=1);

namespace Domain\Finance\Data;

use Spatie\LaravelData\Attributes\Validation\Between;
use Spatie\LaravelData\Attributes\Validation\DateFormat;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

/** Create or edit a one-off expense. The date can't be in the future (checked in the tenant's timezone by the Action). */
final class OneOffExpenseInputData extends Data
{
    public function __construct(
        #[Required, StringType, Max(40)]
        public readonly string $description,
        /** R$ 0,01 … R$ 100.000,00 */
        #[Required, IntegerType, Between(1, 10_000_000)]
        public readonly int $amount_in_cents,
        #[Required, DateFormat('Y-m-d')]
        public readonly string $date,
    ) {}
}
