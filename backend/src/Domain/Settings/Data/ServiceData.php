<?php

declare(strict_types=1);

namespace Domain\Settings\Data;

use Spatie\LaravelData\Data;

/** A service as the barber edits it (with the private cost). */
final class ServiceData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly int $duration_minutes,
        public readonly int $price_in_cents,
        public readonly int $cost_in_cents,
    ) {}
}
