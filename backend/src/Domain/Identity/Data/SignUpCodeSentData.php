<?php

declare(strict_types=1);

namespace Domain\Identity\Data;

use Spatie\LaravelData\Data;

final class SignUpCodeSentData extends Data
{
    public function __construct(
        public readonly int $expires_in,
        public readonly int $resend_after,
    ) {}
}
