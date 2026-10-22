<?php

declare(strict_types=1);

namespace Domain\Client\Data;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

/** The current password, asked again before deleting the account. */
final class ClientPasswordConfirmationData extends Data
{
    public function __construct(
        #[Required, StringType, Max(72)]
        public readonly string $password,
    ) {}
}
