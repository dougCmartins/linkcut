<?php

declare(strict_types=1);

namespace Domain\Client\Data;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

final class ClientPasswordChangeData extends Data
{
    public function __construct(
        #[Required, StringType, Max(72)]
        public readonly string $current_password,
        #[Required, StringType, Min(8), Max(72)]
        public readonly string $password,
    ) {}
}
