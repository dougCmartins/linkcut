<?php

declare(strict_types=1);

namespace Domain\Orchestrator\PublicBooking\Support;

use Domain\Settings\Data\BusinessHourData;
use Domain\Settings\Data\VacationData;

/**
 * Start times a client can request on one day: inside the opening hours, every `interval` minutes,
 * long enough for the service, not overlapping a taken time and, today, not already past.
 * Pure function (no database), so the rule is easy to test.
 */
final class AvailableTimes
{
    /**
     * @param  list<array{0: string, 1: string}>  $busy  taken [starts_at, ends_at) of that day
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
        if (! $hours?->is_open || self::isOnVacation($date, $vacation)) {
            return [];
        }

        $busyRanges = array_map(fn (array $slot): array => [self::minutes($slot[0]), self::minutes($slot[1])], $busy);
        $times = [];
        for ($start = self::minutes($hours->opens_at); $start + $durationMinutes <= self::minutes($hours->closes_at); $start += $intervalMinutes) {
            $end = $start + $durationMinutes;
            $overlaps = array_filter($busyRanges, fn (array $range): bool => $start < $range[1] && $range[0] < $end);
            $hasPassed = $nowMinutes !== null && $start <= $nowMinutes;
            if (! $overlaps && ! $hasPassed) {
                $times[] = sprintf('%02d:%02d', intdiv($start, 60), $start % 60);
            }
        }

        return $times;
    }

    public static function isOnVacation(string $date, VacationData $vacation): bool
    {
        return $vacation->is_enabled
            && (! $vacation->starts_on || $date >= $vacation->starts_on)
            && (! $vacation->ends_on || $date <= $vacation->ends_on);
    }

    private static function minutes(string $time): int
    {
        [$hours, $minutes] = array_map('intval', explode(':', $time));

        return $hours * 60 + $minutes;
    }
}
