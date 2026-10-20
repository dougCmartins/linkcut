<?php

declare(strict_types=1);

namespace Domain\Orchestrator\ClientAppointments\Controllers;

use Domain\Orchestrator\ClientAppointments\Actions\ListClientAppointments;
use Domain\Orchestrator\ClientAppointments\Data\ClientAppointmentData;
use Spatie\LaravelData\DataCollection;

final class ClientAppointmentsController
{
    /** @return DataCollection<int, ClientAppointmentData> */
    public function index(ListClientAppointments $action): DataCollection
    {
        return $action->handle();
    }
}
