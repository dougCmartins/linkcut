<?php

declare(strict_types=1);

namespace Domain\Notification\Data;

use Spatie\LaravelData\Data;

final class StaffInviteMailData extends Data
{
    public function __construct(
        public readonly string $barber_name,
        public readonly string $barber_email,
        public readonly string $unit_name,
        public readonly string $invite_url,
    ) {}
}
