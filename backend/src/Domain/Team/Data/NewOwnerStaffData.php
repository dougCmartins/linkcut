<?php

declare(strict_types=1);

namespace Domain\Team\Data;

use Spatie\LaravelData\Data;

/** Create the owner's staff row for a unit (signup / backfill helpers). */
final class NewOwnerStaffData extends Data
{
    public function __construct(
        public readonly int $tenant_id,
        public readonly int $user_id,
        public readonly string $name,
    ) {}
}
