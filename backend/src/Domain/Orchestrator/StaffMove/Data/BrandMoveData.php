<?php

declare(strict_types=1);

namespace Domain\Orchestrator\StaffMove\Data;

use Spatie\LaravelData\Data;

/** A current move of the brand for "Minha equipe" ("Emprestado para {filial} até {data}"). */
final class BrandMoveData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly string $kind,
        public readonly string $starts_on,
        public readonly ?string $ends_on,
        public readonly string $barber_name,
        public readonly int $from_unit_id,
        public readonly string $from_unit_name,
        public readonly int $to_unit_id,
        public readonly string $to_unit_name,
    ) {}
}
