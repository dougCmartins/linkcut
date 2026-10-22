<?php

declare(strict_types=1);

namespace Domain\Orchestrator\ClientAppointments\Data;

use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

/** Id of one of the client's appointments, from the route. */
final class ClientAppointmentIdData extends Data
{
    public function __construct(
        #[Required, IntegerType, Min(1)]
        public readonly int $id,
    ) {}
}
