<?php

declare(strict_types=1);

namespace Domain\Orchestrator\PublicBooking\Support;

use Domain\Settings\Data\BusinessHourData;
use Domain\Settings\Data\VacationData;

/** Same slot rules as AvailableTimes, but hours come from the barber. */
final class StaffAvailability
{
    /**
     * @param  list<array{0: string, 1: string}>  $busy
     * @return list<string>
     */
    public static function forDay(
        string $date,
        ?BusinessHourData $hours,
        VacationData $vacation,
        int $intervalMinutes,
        int $durationMinutes,
        array $busy,
        ?int $nowMinutes,
    ): array {
        return AvailableTimes::forDay(
            date: $date,
            hours: $hours,
            vacation: $vacation,
            intervalMinutes: $intervalMinutes,
            durationMinutes: $durationMinutes,
            busy: $busy,
            nowMinutes: $nowMinutes,
        );
    }
}
