<?php

declare(strict_types=1);

namespace Domain\Identity\Data;

use Spatie\LaravelData\Data;

/** A code typed by the barber signing up (validated by the sign-up input). */
final class SignUpCodeCheckData extends Data
{
    public function __construct(
        public readonly string $email,
        public readonly string $code,
    ) {}
}
