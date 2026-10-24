<?php

declare(strict_types=1);

namespace Domain\Identity\Data;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

/** The barber's current password, asked again before something that cannot be undone. */
final class UserPasswordConfirmationData extends Data
{
    public function __construct(
        #[Required, StringType, Max(72)]
        public readonly string $password,
    ) {}
}
