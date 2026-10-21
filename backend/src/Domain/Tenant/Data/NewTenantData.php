<?php

declare(strict_types=1);

namespace Domain\Tenant\Data;

use Spatie\LaravelData\Data;

/** A barbershop being created by the sign-up (already validated). */
final class NewTenantData extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly string $slug,
    ) {}
}
