<?php

declare(strict_types=1);

namespace Domain\Settings\Data;

use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

/** A photo in the portfolio form: an uploaded image and its description (required, for screen readers). */
final class PortfolioPhotoInputData extends Data
{
    public function __construct(
        #[Required, IntegerType, Min(1)]
        public readonly int $image_id,
        #[Required, StringType, Max(80)]
        public readonly string $alt,
    ) {}
}
