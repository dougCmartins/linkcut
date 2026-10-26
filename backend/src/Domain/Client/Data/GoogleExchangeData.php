<?php

declare(strict_types=1);

namespace Domain\Client\Data;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

/** The one-time code from `/conta/entrar/google`, traded for a client session. */
final class GoogleExchangeData extends Data
{
    public function __construct(
        #[Required, StringType, Max(128)]
        public readonly string $code,
    ) {}
}
