<?php

declare(strict_types=1);

namespace Domain\Client\Data;

use Spatie\LaravelData\Attributes\Validation\AlphaNumeric;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

/** The one-time code from `/conta/entrar/google` and the nonce this browser kept, traded for a client session. */
final class GoogleExchangeData extends Data
{
    public function __construct(
        #[Required, StringType, Max(128)]
        public readonly string $code,
        #[Required, StringType, AlphaNumeric, Min(32), Max(128)]
        public readonly string $nonce,
    ) {}
}
