<?php

declare(strict_types=1);

namespace Domain\Settings\Data;

use Spatie\LaravelData\Data;

/** A service as the client sees it: never the cost. */
final class PublicServiceData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly int $duration_minutes,
        public readonly int $price_in_cents,
    ) {}
}
