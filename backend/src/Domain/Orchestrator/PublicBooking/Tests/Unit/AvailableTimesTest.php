<?php

declare(strict_types=1);

namespace Domain\Orchestrator\PublicBooking\Tests\Unit;

use Domain\Orchestrator\PublicBooking\Support\AvailableTimes;
use Domain\Settings\Data\BusinessHourData;
use Domain\Settings\Data\VacationData;
use PHPUnit\Framework\TestCase;

final class AvailableTimesTest extends TestCase
{
    private function hours(bool $isOpen = true): BusinessHourData
    {
        return new BusinessHourData(weekday: 1, is_open: $isOpen, opens_at: '09:00', closes_at: '12:00');
    }

    private function noVacation(): VacationData
    {
        return new VacationData(is_enabled: false);
    }

    public function test_offers_every_interval_that_fits_the_service_before_closing(): void
    {
        // Act
        $times = AvailableTimes::forDay('2026-10-19', $this->hours(), $this->noVacation(), 30, 45, [], null);

        // Assert
        $this->assertSame(['09:00', '09:30', '10:00', '10:30', '11:00'], $times);
    }

    public function test_skips_times_that_overlap_a_taken_one_and_times_already_past(): void
    {
        // Act
        $times = AvailableTimes::forDay('2026-10-19', $this->hours(), $this->noVacation(), 30, 30, [['10:00', '10:45']], 9 * 60 + 30);

        // Assert
        $this->assertSame(['11:00', '11:30'], $times);
    }

    public function test_a_closed_day_or_a_vacation_day_has_no_times(): void
    {
        // Arrange
        $vacation = new VacationData(is_enabled: true, starts_on: '2026-10-19', ends_on: '2026-10-20');

        // Act
        $closed = AvailableTimes::forDay('2026-10-19', $this->hours(false), $this->noVacation(), 30, 30, [], null);
        $onVacation = AvailableTimes::forDay('2026-10-20', $this->hours(), $vacation, 30, 30, [], null);
        $afterVacation = AvailableTimes::forDay('2026-10-21', $this->hours(), $vacation, 30, 30, [], null);

        // Assert
        $this->assertSame([], $closed);
        $this->assertSame([], $onVacation);
        $this->assertNotEmpty($afterVacation);
    }
}
