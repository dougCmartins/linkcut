<?php

declare(strict_types=1);

namespace Domain\Team\Data;

use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

final class InviteStaffData extends Data
{
    public function __construct(
        #[Required, StringType, Max(80)]
        public readonly string $name,
        #[Required, Email, Max(255)]
        public readonly string $email,
    ) {}
}
