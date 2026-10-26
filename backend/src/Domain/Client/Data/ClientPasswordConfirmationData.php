<?php

declare(strict_types=1);

namespace Domain\Client\Data;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

/**
 * The current password, asked again before deleting the account.
 * Omitted when the account has no password (it signed in with Google).
 */
final class ClientPasswordConfirmationData extends Data
{
    public function __construct(
        #[Nullable, StringType, Max(72)]
        public readonly ?string $password = null,
    ) {}
}
