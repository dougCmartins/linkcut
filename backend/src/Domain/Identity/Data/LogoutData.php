<?php

declare(strict_types=1);

namespace Domain\Identity\Data;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

final class LogoutData extends Data
{
    public function __construct(
        #[Nullable, StringType, Max(200)]
        public readonly ?string $refresh_token = null,
    ) {}
}
