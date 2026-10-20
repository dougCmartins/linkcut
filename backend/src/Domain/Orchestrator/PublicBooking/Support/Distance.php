<?php

declare(strict_types=1);

namespace Domain\Orchestrator\PublicBooking\Support;

/** Straight-line distance between two points on Earth (haversine). Pure function. */
final class Distance
{
    private const EARTH_RADIUS_METERS = 6_371_000;

    public static function meters(float $fromLatitude, float $fromLongitude, float $toLatitude, float $toLongitude): int
    {
        $latitudeDelta = deg2rad($toLatitude - $fromLatitude);
        $longitudeDelta = deg2rad($toLongitude - $fromLongitude);
        $a = sin($latitudeDelta / 2) ** 2
            + cos(deg2rad($fromLatitude)) * cos(deg2rad($toLatitude)) * sin($longitudeDelta / 2) ** 2;

        return (int) round(2 * self::EARTH_RADIUS_METERS * asin(min(1, sqrt($a))));
    }
}
