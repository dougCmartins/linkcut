<?php

declare(strict_types=1);

namespace Domain\Client\Data;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

/** Where to land after Google. The action keeps only an in-app path. */
final class GoogleRedirectData extends Data
{
    public function __construct(
        #[Nullable, StringType, Max(2048)]
        public readonly ?string $volta = null,
    ) {}
}
