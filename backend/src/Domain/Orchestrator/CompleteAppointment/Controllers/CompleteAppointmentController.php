<?php

declare(strict_types=1);

namespace Domain\Orchestrator\CompleteAppointment\Controllers;

use Domain\Orchestrator\CompleteAppointment\Actions\CompleteAppointment;
use Domain\Orchestrator\CompleteAppointment\Actions\ReopenAppointment;
use Domain\Orchestrator\CompleteAppointment\Data\CompletedAppointmentData;
use Domain\Schedule\Data\BookingRequestData;
use Domain\Schedule\Data\BookingRequestIdData;

final class CompleteAppointmentController
{
    public function complete(int $id, CompleteAppointment $action): CompletedAppointmentData
    {
        return $action->handle(BookingRequestIdData::from(BookingRequestIdData::validate(['id' => $id])));
    }

    public function reopen(int $id, ReopenAppointment $action): BookingRequestData
    {
        return $action->handle(BookingRequestIdData::from(BookingRequestIdData::validate(['id' => $id])));
    }
}
