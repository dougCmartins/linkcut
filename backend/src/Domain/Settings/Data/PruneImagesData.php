<?php

declare(strict_types=1);

namespace Domain\Settings\Data;

use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Data;

/** Uploads younger than this are kept: the barber may still be editing the form that uses them. */
final class PruneImagesData extends Data
{
    public function __construct(
        #[IntegerType, Min(1)]
        public readonly int $older_than_hours = 24,
    ) {}
}
