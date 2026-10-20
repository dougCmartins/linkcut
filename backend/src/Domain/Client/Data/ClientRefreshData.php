<?php

declare(strict_types=1);

namespace Domain\Client\Data;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

/** Refresh token of the session (required to renew; optional on logout, to end the whole session). */
final class ClientRefreshData extends Data
{
    public function __construct(
        #[Nullable, StringType, Max(128)]
        public readonly ?string $refresh_token,
    ) {}
}
