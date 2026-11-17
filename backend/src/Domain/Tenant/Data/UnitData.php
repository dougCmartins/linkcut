<?php

declare(strict_types=1);

namespace Domain\Tenant\Data;

use Spatie\LaravelData\Data;

/** One unit (the main one or a branch) as the unit picker shows it. */
final class UnitData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $slug,
        public readonly bool $is_main,
        public readonly bool $active,
        public readonly bool $onboarded,
    ) {}
}
