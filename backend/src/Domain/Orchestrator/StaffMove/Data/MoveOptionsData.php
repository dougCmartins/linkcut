<?php

declare(strict_types=1);

namespace Domain\Orchestrator\StaffMove\Data;

use Domain\Team\Data\StaffBusinessHourData;
use Spatie\LaravelData\Data;

/** What the move form starts with: the destination unit's hours (a suggestion to edit) and its services. */
final class MoveOptionsData extends Data
{
    /**
     * @param  list<StaffBusinessHourData>  $weekly_hours
     * @param  list<MoveServiceData>  $services
     */
    public function __construct(
        public readonly array $weekly_hours,
        public readonly array $services,
    ) {}
}
