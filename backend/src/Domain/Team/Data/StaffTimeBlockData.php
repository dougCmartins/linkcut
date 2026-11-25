<?php

declare(strict_types=1);

namespace Domain\Team\Data;

use Domain\Team\Enums\StaffTimeBlockRecurrence;
use Spatie\LaravelData\Data;

final class StaffTimeBlockData extends Data
{
    /**
     * @param  list<int>|null  $weekdays
     */
    public function __construct(
        public readonly int $id,
        public readonly int $staff_id,
        public readonly string $starts_at,
        public readonly string $ends_at,
        public readonly ?StaffTimeBlockRecurrence $recurrence,
        public readonly ?array $weekdays,
        public readonly ?string $note,
    ) {}
}
