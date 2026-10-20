<?php

declare(strict_types=1);

namespace Domain\Orchestrator\PublicBooking\Data;

use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Data;

/** GET /public/{slug}/availability?service_id — without a service, the shortest one is used. */
final class AvailabilityQueryData extends Data
{
    public function __construct(
        #[Nullable, IntegerType, Min(1)]
        public readonly ?int $service_id = null,
    ) {}
}
