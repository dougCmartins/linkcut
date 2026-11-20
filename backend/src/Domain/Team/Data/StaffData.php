<?php

declare(strict_types=1);

namespace Domain\Team\Data;

use Spatie\LaravelData\Data;

final class StaffData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly int $tenant_id,
        public readonly ?int $user_id,
        public readonly string $name,
        public readonly ?string $avatar,
        public readonly bool $active,
        public readonly bool $pending_invite,
        public readonly StaffPermissionsData $permissions,
    ) {}
}
