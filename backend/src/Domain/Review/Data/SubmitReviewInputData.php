<?php

declare(strict_types=1);

namespace Domain\Review\Data;

use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

/** What the client sends: a whole-star rating and an optional comment. */
final class SubmitReviewInputData extends Data
{
    public function __construct(
        #[IntegerType, Min(1), Max(5)]
        public readonly int $rating,
        #[Nullable, StringType, Max(500)]
        public readonly ?string $comment = null,
    ) {}
}
