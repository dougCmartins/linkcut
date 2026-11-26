<?php

declare(strict_types=1);

namespace Domain\Team\Data;

use Spatie\LaravelData\Data;

/** The barber's place at the destination unit (the current tenant): same person, own row there. */
final class PlaceStaffData extends Data
{
    public function __construct(
        public readonly int $user_id,
        public readonly string $name,
        public readonly ?string $avatar,
        /** Their row at the origin: its switches are copied when `keep_permissions`. */
        public readonly int $from_staff_id,
        public readonly bool $keep_permissions,
    ) {}
}
