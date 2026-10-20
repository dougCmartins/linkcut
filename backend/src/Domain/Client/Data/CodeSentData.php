<?php

declare(strict_types=1);

namespace Domain\Client\Data;

use Spatie\LaravelData\Data;

/** Always the same answer, whether or not the e-mail has an account (nobody can probe e-mails). */
final class CodeSentData extends Data
{
    public function __construct(
        public readonly int $expires_in,
        public readonly int $resend_after,
    ) {}
}
