<?php

declare(strict_types=1);

namespace Domain\Notification\Data;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

/** Which device stops receiving (on logout or when the barber turns it off). */
final class PushEndpointData extends Data
{
    public function __construct(
        #[Required, StringType, Max(500)]
        public readonly string $endpoint,
    ) {}
}
