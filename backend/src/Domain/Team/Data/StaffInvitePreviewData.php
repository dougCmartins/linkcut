<?php

declare(strict_types=1);

namespace Domain\Team\Data;

use Spatie\LaravelData\Data;

final class StaffInvitePreviewData extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $unit_name,
        public readonly bool $expired,
        public readonly bool $accepted,
    ) {}
}
