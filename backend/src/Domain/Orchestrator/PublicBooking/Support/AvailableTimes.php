<?php

declare(strict_types=1);

namespace Domain\Orchestrator\PublicBooking\Support;

use Carbon\CarbonImmutable;
use Domain\Settings\Data\BookingOptionsData;
use Domain\Settings\Data\BusinessHourData;
use Domain\Settings\Data\PublicServiceData;
use Domain\Settings\Data\VacationData;
use Illuminate\Validation\ValidationException;

/**
 * Start times a client can request on one day: inside the opening hours, every `interval` minutes,
 * long enough for the service, not overlapping a taken time and, today, not already past.
 * Pure function (no database), so the rule is easy to test.
 */
final class AvailableTimes
{
    /**
     * Free times of one day of the booking window for a service.
     *
     * @param  list<array{0: string, 1: string}>  $busy  taken [starts_at, ends_at) of that day
     * @return list<string>
     */
    public static function forDate(BookingOptionsData $options, PublicServiceData $service, CarbonImmutable $day, CarbonImmutable $now, array $busy): array
    {
        $hours = collect($options->weekly_hours->all())->first(fn (BusinessHourData $item): bool => $item->weekday === $day->dayOfWeek);

        return self::forDay(
            date: $day->toDateString(),
            hours: $hours,
            vacation: $options->vacation,
            intervalMinutes: $options->slot_interval_minutes,
            durationMinutes: $service->duration_minutes,
            busy: $busy,
            nowMinutes: $day->isSameDay($now) ? $now->hour * 60 + $now->minute : null,
        );
    }

    /** The chosen service, or the shortest one when none was chosen. */
    public static function service(BookingOptionsData $options, ?int $serviceId): PublicServiceData
    {
        $services = collect($options->services->all());
        $service = $serviceId
            ? $services->first(fn (PublicServiceData $item): bool => $item->id === $serviceId)
            : $services->sortBy('duration_minutes')->first();

        return $service ?? throw ValidationException::withMessages(['service_id' => 'exists']);
    }

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
