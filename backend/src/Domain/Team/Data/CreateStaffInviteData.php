<?php

declare(strict_types=1);

namespace Domain\Team\Data;

use Spatie\LaravelData\Data;

/** Internal: create staff + invite rows; plain token returned once for the e-mail. */
final class CreateStaffInviteData extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly int $created_by,
    ) {}
}
