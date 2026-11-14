<?php

declare(strict_types=1);

namespace Domain\Settings\Data;

use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

/** Id of an image that belongs to the current barbershop. */
final class ImageIdData extends Data
{
    public function __construct(
        #[Required, IntegerType, Min(1)]
        public readonly int $id,
    ) {}
}
