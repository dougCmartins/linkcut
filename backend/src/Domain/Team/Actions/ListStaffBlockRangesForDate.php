<?php

declare(strict_types=1);

namespace Domain\Team\Actions;

use Carbon\CarbonImmutable;
use Domain\Team\Data\StaffIdData;
use Domain\Team\Enums\StaffTimeBlockRecurrence;
use Domain\Team\Models\StaffTimeBlock;

/**
 * Busy [starts_at, ends_at) time strings ("H:i") for one staff on one calendar day.
 *
 * @return list<array{0: string, 1: string}>
 */
final class ListStaffBlockRangesForDate
{
    public function handle(StaffIdData $staff, string $date): array
    {
        $day = CarbonImmutable::createFromFormat('!Y-m-d', $date);
        if (! $day) {
            return [];
        }

        $dayStart = $day->startOfDay();
        $dayEnd = $day->endOfDay();
        $ranges = [];

        $blocks = StaffTimeBlock::query()->where('staff_id', $staff->id)->get();
        foreach ($blocks as $block) {
            if ($block->recurrence === StaffTimeBlockRecurrence::Weekly) {
                $weekdays = $block->weekdays ?? [];
                if (! in_array($day->dayOfWeek, $weekdays, true)) {
                    continue;
                }
                if ($day->toDateString() < $block->starts_at->toDateString()) {
                    continue;
                }
                // When starts_at and ends_at fall on different calendar days, ends_at dates the last occurrence.
                $openEnded = $block->starts_at->toDateString() === $block->ends_at->toDateString();
                if (! $openEnded && $day->toDateString() > $block->ends_at->toDateString()) {
                    continue;
                }
                $ranges[] = [
                    $block->starts_at->format('H:i'),
                    $block->ends_at->format('H:i'),
                ];

                continue;
            }

            if ($block->ends_at->lte($dayStart) || $block->starts_at->gt($dayEnd)) {
                continue;
            }
            $start = $block->starts_at->greaterThan($dayStart) ? $block->starts_at : $dayStart;
            $end = $block->ends_at->lessThan($dayEnd) ? $block->ends_at : $dayEnd;
            $ranges[] = [$start->format('H:i'), $end->format('H:i')];
        }

        return $ranges;
    }
}
