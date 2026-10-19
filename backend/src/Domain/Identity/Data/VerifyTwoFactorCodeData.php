<?php

declare(strict_types=1);

namespace Domain\Identity\Data;

use Spatie\LaravelData\Attributes\Validation\Regex;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Ulid;
use Spatie\LaravelData\Data;

final class VerifyTwoFactorCodeData extends Data
{
    public function __construct(
        #[Required, StringType, Ulid]
        public readonly string $challenge_id,
        #[Required, StringType, Regex('/^\d{6}$/')]
        public readonly string $code,
    ) {}
}
