<?php

declare(strict_types=1);

namespace Domain\Orchestrator\Onboarding\Data;

use Spatie\LaravelData\Attributes\Validation\Accepted;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Regex;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

/** Everything the sign-up screen sends at the end: the barber, the code from the e-mail and the barbershop. */
final class SignUpData extends Data
{
    public function __construct(
        #[Required, StringType, Min(2), Max(60)]
        public readonly string $name,
        #[Required, StringType, Email, Max(120)]
        public readonly string $email,
        #[Required, StringType, Min(8), Max(72)]
        public readonly string $password,
        #[Required, Regex('/^\d{6}$/')]
        public readonly string $code,
        #[Required, StringType, Min(2), Max(40)]
        public readonly string $barbershop_name,
        /** linkcut.app/{slug} */
        #[Required, StringType, Min(3), Max(40), Regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')]
        public readonly string $slug,
        /** "Li e aceito os Termos de Uso e a Política de Privacidade" (required). */
        #[Accepted]
        public readonly bool $accept_terms = false,
    ) {}
}
