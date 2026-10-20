<?php

declare(strict_types=1);

namespace Domain\Client\Data;

use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Regex;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

/** The code from the e-mail and the password to create (sign-up) or replace (forgot it). */
final class ClientPasswordData extends Data
{
    public function __construct(
        #[Required, Email, Max(120)]
        public readonly string $email,
        #[Required, StringType, Regex('/^\d{6}$/')]
        public readonly string $code,
        #[Required, StringType, Min(8), Max(72)]
        public readonly string $password,
    ) {}
}
