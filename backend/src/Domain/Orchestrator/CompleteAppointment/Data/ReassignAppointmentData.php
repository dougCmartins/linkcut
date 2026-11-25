<?php

declare(strict_types=1);

namespace Domain\Orchestrator\CompleteAppointment\Data;

use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

final class ReassignAppointmentData extends Data
{
    public function __construct(
        #[Required, IntegerType, Min(1)]
        public readonly int $staff_id,
    ) {}
}
