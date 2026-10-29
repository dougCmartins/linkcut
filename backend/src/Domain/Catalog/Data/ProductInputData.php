<?php

declare(strict_types=1);

namespace Domain\Catalog\Data;

use Spatie\LaravelData\Attributes\Validation\Between;
use Spatie\LaravelData\Attributes\Validation\BooleanType;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

/** Creating or editing a product. Active defaults to true. The description is cleaned after this validation. */
final class ProductInputData extends Data
{
    public function __construct(
        #[Required, StringType, Max(80)]
        public readonly string $name,
        /** R$ 0,01 … R$ 10.000,00 */
        #[Required, IntegerType, Between(1, 1_000_000)]
        public readonly int $price_in_cents,
        #[Nullable, StringType, Max(160)]
        public readonly ?string $description = null,
        #[Nullable, IntegerType, Min(1)]
        public readonly ?int $image_id = null,
        #[BooleanType]
        public readonly bool $is_active = true,
    ) {}
}
