<?php

declare(strict_types=1);

namespace Domain\Tenant\Data;

use Spatie\LaravelData\Data;

/** A barbershop / unit being created (already validated). */
final class NewTenantData extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly string $slug,
        public readonly int $organization_id,
        public readonly bool $is_main = true,
    ) {}
}
