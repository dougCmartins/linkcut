<?php

declare(strict_types=1);

namespace Domain\Team\Data;

use Spatie\LaravelData\Attributes\Validation\Between;
use Spatie\LaravelData\Attributes\Validation\BooleanType;
use Spatie\LaravelData\Attributes\Validation\DateFormat;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

final class StaffBusinessHourData extends Data
{
    public function __construct(
        #[Required, IntegerType, Between(0, 6)]
        public readonly int $weekday,
        #[Required, BooleanType]
        public readonly bool $is_open,
        #[Required, DateFormat('H:i')]
        public readonly string $opens_at,
        #[Required, DateFormat('H:i')]
        public readonly string $closes_at,
        /** Optional break inside the day (both or none, inside opens_at–closes_at). */
        #[Nullable, DateFormat('H:i')]
        public readonly ?string $break_starts_at = null,
        #[Nullable, DateFormat('H:i')]
        public readonly ?string $break_ends_at = null,
    ) {}

    /**
     * The break as a taken range of the day, so the free times skip it like a block.
     *
     * @return list<array{0: string, 1: string}>
     */
    public function breakRanges(): array
    {
        return $this->break_starts_at !== null && $this->break_ends_at !== null
            ? [[$this->break_starts_at, $this->break_ends_at]]
            : [];
    }
}
