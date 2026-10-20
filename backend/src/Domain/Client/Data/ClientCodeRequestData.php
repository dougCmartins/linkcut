<?php

declare(strict_types=1);

namespace Domain\Client\Data;

use Domain\Client\Enums\CodePurpose;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Enum;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

/** POST /client/codes: send a code to complete the sign-up or to create a new password. */
final class ClientCodeRequestData extends Data
{
    public function __construct(
        #[Required, Email, Max(120)]
        public readonly string $email,
        #[Required, Enum(CodePurpose::class)]
        public readonly CodePurpose $purpose,
    ) {}
}
