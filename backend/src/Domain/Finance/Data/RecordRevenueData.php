<?php

declare(strict_types=1);

namespace Domain\Finance\Data;

use Spatie\LaravelData\Data;

/** A concluded appointment going into the history (from the Schedule domain, stage 3). */
final class RecordRevenueData extends Data
{
    public function __construct(
        public readonly int $booking_request_id,
        public readonly string $date,
        public readonly string $time,
        public readonly string $client_name,
        public readonly string $service_name,
        public readonly int $price_in_cents,
        public readonly int $cost_in_cents,
    ) {}
}
