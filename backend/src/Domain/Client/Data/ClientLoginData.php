<?php

declare(strict_types=1);

namespace Domain\Client\Data;

use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

final class ClientLoginData extends Data
{
    public function __construct(
        #[Required, Email, Max(120)]
        public readonly string $email,
        #[Required, StringType, Max(72)]
        public readonly string $password,
    ) {}
}
