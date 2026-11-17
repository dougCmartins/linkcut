<?php

declare(strict_types=1);

namespace Domain\Tenant\Data;

use Spatie\LaravelData\Data;

/** A signed-in user asking for a unit: their own unit and organization, and the unit they picked (null = their own). */
final class UnitAccessData extends Data
{
    public function __construct(
        public readonly int $user_id,
        public readonly int $home_tenant_id,
        public readonly ?int $organization_id,
        public readonly ?int $unit_id = null,
    ) {}
}
