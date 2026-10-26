<?php

declare(strict_types=1);

namespace Domain\Client\Data;

use Spatie\LaravelData\Attributes\Validation\AlphaNumeric;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

/**
 * Where to land after Google (the action keeps only an in-app path), and the nonce the SPA kept in this
 * browser: only the browser that started the sign-in can trade the code at the end.
 */
final class GoogleRedirectData extends Data
{
    public function __construct(
        #[Required, StringType, AlphaNumeric, Min(32), Max(128)]
        public readonly string $nonce,
        #[Nullable, StringType, Max(2048)]
        public readonly ?string $volta = null,
    ) {}
}
