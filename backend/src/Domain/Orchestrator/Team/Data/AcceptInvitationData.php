<?php

declare(strict_types=1);

namespace Domain\Orchestrator\Team\Data;

use Spatie\LaravelData\Attributes\Validation\Accepted;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

/** "Aceitar o convite": the token from the e-mail, the barber's new password and the terms. */
final class AcceptInvitationData extends Data
{
    public function __construct(
        #[Required, StringType, Min(20), Max(120)]
        public readonly string $token,
        #[Required, StringType, Min(8), Max(72)]
        public readonly string $password,
        #[Accepted]
        public readonly bool $accept_terms = false,
    ) {}
}
