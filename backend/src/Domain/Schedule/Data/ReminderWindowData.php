<?php

declare(strict_types=1);

namespace Domain\Schedule\Data;

use Spatie\LaravelData\Data;

/** Barbershops that share a timezone and the "Y-m-d H:i" window (in that timezone) of their reminders. */
final class ReminderWindowData extends Data
{
    /** @param list<int> $tenant_ids */
    public function __construct(
        public readonly array $tenant_ids,
        public readonly string $from,
        public readonly string $to,
    ) {}
}
