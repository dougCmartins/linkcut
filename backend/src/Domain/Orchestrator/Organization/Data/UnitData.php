<?php

declare(strict_types=1);

namespace Domain\Orchestrator\Organization\Data;

use Spatie\LaravelData\Data;

final class UnitData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $slug,
        public readonly bool $is_main,
    ) {}
}
