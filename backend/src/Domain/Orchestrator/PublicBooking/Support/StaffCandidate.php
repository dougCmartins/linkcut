<?php

declare(strict_types=1);

namespace Domain\Orchestrator\PublicBooking\Support;

/** A barber free at the asked time, with what the "any barber" rule weighs. */
final class StaffCandidate
{
    public function __construct(
        public readonly int $staff_id,
        /** Pending and confirmed bookings of the barber that day. */
        public readonly int $bookings_of_day,
        /** Minutes between the end of what comes before (booking, block, start of the day) and the asked time. */
        public readonly int $gap_minutes,
    ) {}
}
