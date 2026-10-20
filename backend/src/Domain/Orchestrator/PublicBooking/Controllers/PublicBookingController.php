<?php

declare(strict_types=1);

namespace Domain\Orchestrator\PublicBooking\Controllers;

use Domain\Orchestrator\PublicBooking\Actions\RequestBooking;
use Domain\Orchestrator\PublicBooking\Actions\ShowAvailability;
use Domain\Orchestrator\PublicBooking\Data\AvailabilityData;
use Domain\Orchestrator\PublicBooking\Data\AvailabilityQueryData;
use Domain\Orchestrator\PublicBooking\Data\BookingConfirmationData;
use Domain\Orchestrator\PublicBooking\Data\BookingRequestInputData;

final class PublicBookingController
{
    public function availability(AvailabilityQueryData $data, ShowAvailability $action): AvailabilityData
    {
        return $action->handle($data);
    }

    public function store(BookingRequestInputData $data, RequestBooking $action): BookingConfirmationData
    {
        return $action->handle($data);
    }
}
