<?php

declare(strict_types=1);

namespace Domain\Orchestrator\CompleteAppointment\Data;

use Domain\Schedule\Data\BookingRequestData;
use Spatie\LaravelData\Data;

/** The concluded appointment and what went into the revenue (price and cost of that moment). */
final class CompletedAppointmentData extends Data
{
    public function __construct(
        public readonly BookingRequestData $request,
        public readonly int $price_in_cents,
        public readonly int $cost_in_cents,
    ) {}
}
