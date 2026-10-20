<?php

declare(strict_types=1);

namespace Domain\Finance\Data;

use Spatie\LaravelData\Attributes\Validation\Between;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

/** Create or edit a fixed expense. */
final class FixedExpenseInputData extends Data
{
    public function __construct(
        #[Required, StringType, Max(40)]
        public readonly string $name,
        /** R$ 0,01 … R$ 100.000,00 */
        #[Required, IntegerType, Between(1, 10_000_000)]
        public readonly int $amount_in_cents,
        /** Day of the month it is paid; in shorter months it falls on the last day. */
        #[Required, IntegerType, Between(1, 31)]
        public readonly int $due_day,
    ) {}
}
