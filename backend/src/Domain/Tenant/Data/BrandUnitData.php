<?php

declare(strict_types=1);

namespace Domain\Tenant\Data;

use Spatie\LaravelData\Data;

/** One active unit of a brand, as the brand link lists it. */
final class BrandUnitData extends Data
{
    public function __construct(
        public readonly int $tenant_id,
        public readonly string $slug,
        public readonly string $name,
        public readonly bool $is_main,
    ) {}
}
