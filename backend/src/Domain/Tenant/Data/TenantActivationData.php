<?php

declare(strict_types=1);

namespace Domain\Tenant\Data;

use Spatie\LaravelData\Data;

final class TenantActivationData extends Data
{
    public function __construct(
        public readonly string $slug,
        public readonly bool $active,
    ) {}
}
