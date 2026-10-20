<?php

declare(strict_types=1);

namespace Domain\Orchestrator\PublicBooking\Tests\Unit;

use Domain\Orchestrator\PublicBooking\Support\Distance;
use PHPUnit\Framework\TestCase;

final class DistanceTest extends TestCase
{
    public function test_measures_the_straight_line_between_two_points(): void
    {
        // Act: Praça da Sé (São Paulo) → Centro (Rio de Janeiro)
        $meters = Distance::meters(-23.5505, -46.6333, -22.9068, -43.1729);

        // Assert
        $this->assertSame(360_749, $meters);
    }

    public function test_the_same_point_is_zero(): void
    {
        // Act
        $meters = Distance::meters(-23.5578, -46.6609, -23.5578, -46.6609);

        // Assert
        $this->assertSame(0, $meters);
    }
}
