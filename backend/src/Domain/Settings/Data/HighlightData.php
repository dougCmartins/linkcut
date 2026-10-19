<?php

declare(strict_types=1);

namespace Domain\Settings\Data;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

/** "10 anos" + "de experiência". An empty value is not shown on the page. */
final class HighlightData extends Data
{
    public function __construct(
        #[Nullable, StringType, Max(16)]
        public readonly ?string $label,
        #[Nullable, StringType, Max(24)]
        public readonly ?string $value,
    ) {}
}
