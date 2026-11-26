<?php

declare(strict_types=1);

namespace Domain\Orchestrator\PublicBooking\Support;

/**
 * "Qualquer um disponível" (decision 0002): share the work instead of filling the oldest barber first.
 * Among the barbers free at the asked time: fewest bookings that day, then the smallest gap before the time,
 * then the lowest staff id.
 */
final class PickAnyStaff
{
    /** @param list<StaffCandidate> $candidates */
    public static function pick(array $candidates): ?int
    {
        usort($candidates, fn (StaffCandidate $a, StaffCandidate $b): int => [$a->bookings_of_day, $a->gap_minutes, $a->staff_id]
            <=> [$b->bookings_of_day, $b->gap_minutes, $b->staff_id]);

        return $candidates[0]->staff_id ?? null;
    }

    /**
     * Gap before the asked start: from the latest end of a busy range (booking, block, break) that ends at or before
     * the start, or from the opening of the day when nothing comes before.
     *
     * @param  list<array{0: string, 1: string}>  $busy  "HH:mm" ranges
     */
    public static function gapMinutes(string $startsAt, string $opensAt, array $busy): int
    {
        $start = self::minutes($startsAt);
        $previousEnd = self::minutes($opensAt);
        foreach ($busy as [, $endsAt]) {
            $end = self::minutes($endsAt);
            if ($end <= $start && $end > $previousEnd) {
                $previousEnd = $end;
            }
        }

        return max(0, $start - $previousEnd);
    }

    private static function minutes(string $time): int
    {
        [$hours, $minutes] = array_map('intval', explode(':', substr($time, 0, 5)));

        return $hours * 60 + $minutes;
    }
}
