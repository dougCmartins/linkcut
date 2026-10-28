<?php

declare(strict_types=1);

namespace Domain\Review\Data;

use Spatie\LaravelData\Data;

/** Average of the reviews the barber has not hidden. Null when the option is off or nobody has rated. */
final class PublicRatingData extends Data
{
    public function __construct(
        public readonly ?float $average,
        public readonly int $count,
    ) {}
}
