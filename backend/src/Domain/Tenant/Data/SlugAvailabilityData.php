<?php

declare(strict_types=1);

namespace Domain\Tenant\Data;

use Spatie\LaravelData\Data;

final class SlugAvailabilityData extends Data
{
    public function __construct(
        public readonly string $slug,
        public readonly bool $available,
        /** A free link close to the one asked (the same one when it is free). */
        public readonly string $suggestion,
    ) {}
}
