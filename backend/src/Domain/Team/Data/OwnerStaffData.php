<?php

declare(strict_types=1);

namespace Domain\Team\Data;

use Spatie\LaravelData\Data;

/** The owner as the first barber of their unit (sign-up). */
final class OwnerStaffData extends Data
{
    public function __construct(
        public readonly int $user_id,
        public readonly string $name,
        public readonly string $email,
    ) {}
}
