<?php

declare(strict_types=1);

namespace Domain\Orchestrator\PublicBooking\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

/** Every day of the booking window (from today, tenant timezone) with its free times. No client data. */
final class AvailabilityData extends Data
{
    /** @param DataCollection<int, AvailableDayData> $days */
    public function __construct(
        public readonly int $service_id,
        #[DataCollectionOf(AvailableDayData::class)]
        public readonly DataCollection $days,
    ) {}
}
