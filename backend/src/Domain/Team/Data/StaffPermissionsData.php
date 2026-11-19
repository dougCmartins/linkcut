<?php

declare(strict_types=1);

namespace Domain\Team\Data;

use Spatie\LaravelData\Attributes\Validation\BooleanType;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

/** The 3 switches the owner controls for each barber (all off by default). */
final class StaffPermissionsData extends Data
{
    public function __construct(
        /** Prices and values of services and appointments. */
        #[Required, BooleanType]
        public readonly bool $see_prices = false,
        /** Their own commission (docs/decisoes/0001 P5). */
        #[Required, BooleanType]
        public readonly bool $see_own_commission = false,
        /** The whole unit's agenda, not only their own. */
        #[Required, BooleanType]
        public readonly bool $see_unit_agenda = false,
    ) {}
}
