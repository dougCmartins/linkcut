<?php

declare(strict_types=1);

namespace Domain\Team\Data;

use Spatie\LaravelData\Data;

/** A move of one brand (the brand is checked: moves sit above the units). */
final class StaffMoveIdData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly int $organization_id,
    ) {}
}
