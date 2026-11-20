<?php

declare(strict_types=1);

namespace Domain\Team\Data;

use Spatie\LaravelData\Attributes\Validation\BooleanType;
use Spatie\LaravelData\Data;

final class StaffPermissionsData extends Data
{
    public function __construct(
        #[BooleanType]
        public readonly bool $see_prices = false,
        #[BooleanType]
        public readonly bool $see_own_commission = false,
        #[BooleanType]
        public readonly bool $see_unit_agenda = false,
    ) {}
}
