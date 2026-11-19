<?php

declare(strict_types=1);

namespace Domain\Team\Data;

use Spatie\LaravelData\Attributes\Validation\BooleanType;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

/** The owner turns a barber on or off and sets what they see. */
final class StaffUpdateData extends Data
{
    public function __construct(
        #[Required, BooleanType]
        public readonly bool $active,
        #[Required]
        public readonly StaffPermissionsData $permissions,
    ) {}
}
