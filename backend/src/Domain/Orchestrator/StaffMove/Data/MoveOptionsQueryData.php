<?php

declare(strict_types=1);

namespace Domain\Orchestrator\StaffMove\Data;

use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

final class MoveOptionsQueryData extends Data
{
    public function __construct(
        #[Required, IntegerType, Min(1)]
        public readonly int $to_unit_id,
    ) {}
}
