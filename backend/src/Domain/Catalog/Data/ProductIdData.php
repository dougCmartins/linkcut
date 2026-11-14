<?php

declare(strict_types=1);

namespace Domain\Catalog\Data;

use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

/** Id of a product from the route. */
final class ProductIdData extends Data
{
    public function __construct(
        #[Required, IntegerType, Min(1)]
        public readonly int $id,
    ) {}
}
