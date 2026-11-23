<?php

declare(strict_types=1);

namespace Domain\Team\Data;

use Spatie\LaravelData\Data;

/** After a valid invite is consumed: ids needed to create the barber user. */
final class ConsumeStaffInviteResultData extends Data
{
    public function __construct(
        public readonly int $invite_id,
        public readonly int $staff_id,
        public readonly int $tenant_id,
        public readonly string $email,
        public readonly string $staff_name,
    ) {}
}
