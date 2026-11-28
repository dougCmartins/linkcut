<?php

declare(strict_types=1);

namespace Domain\Team\Data;

use Spatie\LaravelData\Data;

/** A current move of the brand, with the barber's name and the unit of each side. */
final class ActiveStaffMoveData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly string $kind,
        public readonly string $starts_on,
        public readonly ?string $ends_on,
        public readonly string $barber_name,
        public readonly int $from_unit_id,
        public readonly int $to_unit_id,
    ) {}
}
