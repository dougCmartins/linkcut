<?php

declare(strict_types=1);

namespace Domain\Orchestrator\SignIn\Data;

use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

/** Sign-in from the barbershop's own page (/<slug>/entrar). */
final class BarberLoginData extends Data
{
    public function __construct(
        #[Required, Email, Max(255)]
        public readonly string $email,
        #[Required, StringType, Max(255)]
        public readonly string $password,
        #[Required, StringType, Max(60)]
        public readonly string $slug,
    ) {}
}
