<?php

declare(strict_types=1);

namespace Domain\Orchestrator\PublicBooking\Tests\Unit;

use Domain\Orchestrator\PublicBooking\Support\PickAnyStaff;
use Domain\Orchestrator\PublicBooking\Support\StaffCandidate;
use PHPUnit\Framework\TestCase;

final class PickAnyStaffTest extends TestCase
{
    public function test_the_barber_with_fewer_bookings_that_day_wins(): void
    {
        // Arrange
        $candidates = [
            new StaffCandidate(staff_id: 1, bookings_of_day: 3, gap_minutes: 0),
            new StaffCandidate(staff_id: 2, bookings_of_day: 1, gap_minutes: 120),
        ];

        // Act
        $picked = PickAnyStaff::pick($candidates);

        // Assert
        $this->assertSame(2, $picked);
    }

    public function test_on_a_tie_of_bookings_the_smallest_gap_wins(): void
    {
        // Arrange
        $candidates = [
            new StaffCandidate(staff_id: 1, bookings_of_day: 2, gap_minutes: 90),
            new StaffCandidate(staff_id: 2, bookings_of_day: 2, gap_minutes: 15),
        ];

        // Act
        $picked = PickAnyStaff::pick($candidates);

        // Assert
        $this->assertSame(2, $picked);
    }

    public function test_on_a_full_tie_the_lowest_staff_id_wins(): void
    {
        // Arrange
        $candidates = [
            new StaffCandidate(staff_id: 7, bookings_of_day: 0, gap_minutes: 60),
            new StaffCandidate(staff_id: 4, bookings_of_day: 0, gap_minutes: 60),
        ];

        // Act
        $picked = PickAnyStaff::pick($candidates);

        // Assert
        $this->assertSame(4, $picked);
    }

    public function test_nobody_free_picks_nobody(): void
    {
        // Act
        $picked = PickAnyStaff::pick([]);

        // Assert
        $this->assertNull($picked);
    }

    public function test_the_gap_counts_from_the_latest_end_before_the_time_or_from_the_opening(): void
    {
        // Arrange
        $busy = [['09:00', '09:45'], ['12:00', '13:00'], ['16:00', '17:00']];

        // Act
        $afterLunch = PickAnyStaff::gapMinutes('14:00', '09:00', $busy);
        $rightAfter = PickAnyStaff::gapMinutes('09:45', '09:00', $busy);
        $emptyDay = PickAnyStaff::gapMinutes('11:00', '09:00', []);

        // Assert
        $this->assertSame(60, $afterLunch);
        $this->assertSame(0, $rightAfter);
        $this->assertSame(120, $emptyDay);
    }
}
