<?php

declare(strict_types=1);

namespace Domain\Orchestrator\PublicBooking\Data;

use Spatie\LaravelData\Data;

final class AvailableDayData extends Data
{
    /** @param list<string> $times "HH:mm" */
    public function __construct(
        public readonly string $date,
        public readonly array $times,
    ) {}
}
