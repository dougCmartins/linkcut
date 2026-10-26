<?php

declare(strict_types=1);

namespace Domain\Client\Data;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

/** What Google sends back. `error` is set when the client cancels. */
final class GoogleCallbackData extends Data
{
    public function __construct(
        #[Nullable, StringType, Max(2048)]
        public readonly ?string $code = null,
        #[Nullable, StringType, Max(255)]
        public readonly ?string $state = null,
        #[Nullable, StringType, Max(120)]
        public readonly ?string $error = null,
    ) {}
}
