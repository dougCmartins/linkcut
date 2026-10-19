<?php

declare(strict_types=1);

namespace Domain\Identity\Data;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

final class RefreshSessionData extends Data
{
    public function __construct(
        #[Required, StringType, Max(200)]
        public readonly string $refresh_token,
    ) {}
}
