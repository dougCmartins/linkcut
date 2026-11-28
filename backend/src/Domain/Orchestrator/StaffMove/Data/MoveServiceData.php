<?php

declare(strict_types=1);

namespace Domain\Orchestrator\StaffMove\Data;

use Spatie\LaravelData\Data;

/** A service of the destination unit the barber can be given (no prices: the form only needs the names). */
final class MoveServiceData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
    ) {}
}
