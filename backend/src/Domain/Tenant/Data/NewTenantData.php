<?php

declare(strict_types=1);

namespace Domain\Tenant\Data;

use Spatie\LaravelData\Data;

/**
 * A barbershop being created (already validated). Without organization: a sign-up, which gets its own individual
 * organization. With one: a new branch of that organization.
 */
final class NewTenantData extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly string $slug,
        public readonly ?int $organization_id = null,
    ) {}
}
