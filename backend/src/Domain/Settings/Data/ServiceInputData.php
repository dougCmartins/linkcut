<?php

declare(strict_types=1);

namespace Domain\Settings\Data;

use Spatie\LaravelData\Attributes\Validation\Between;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\LessThan;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\MultipleOf;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

/** A service in the "Agenda" form. Without id it is new; a known id is updated. */
final class ServiceInputData extends Data
{
    public function __construct(
        #[Required, StringType, Max(30)]
        public readonly string $name,
        #[Required, IntegerType, Between(5, 240), MultipleOf(5)]
        public readonly int $duration_minutes,
        /** R$ 0,01 … R$ 10.000,00 */
        #[Required, IntegerType, Between(1, 1_000_000)]
        public readonly int $price_in_cents,
        /** Private. Zero = no cost; otherwise below the price. */
        #[Required, IntegerType, Min(0), LessThan('price_in_cents')]
        public readonly int $cost_in_cents,
        #[Nullable, IntegerType, Min(1)]
        public readonly ?int $id = null,
    ) {}
}
