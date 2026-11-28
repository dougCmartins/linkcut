<?php

declare(strict_types=1);

namespace Domain\Team\Data;

use Spatie\LaravelData\Data;

/** An active loan with the units on each side (the rows belong to two tenants of the brand). */
final class ActiveLoanData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly int $user_id,
        public readonly int $from_staff_id,
        public readonly int $from_tenant_id,
        public readonly int $to_staff_id,
        public readonly int $to_tenant_id,
        public readonly string $ends_on,
    ) {}
}
