<?php

declare(strict_types=1);

namespace Domain\Schedule\Controllers;

use Domain\Schedule\Actions\ListBookingRequests;
use Domain\Schedule\Actions\ShowTodayAgenda;
use Domain\Schedule\Data\BookingRequestData;
use Domain\Schedule\Data\BookingRequestQueryData;
use Spatie\LaravelData\DataCollection;

final class ScheduleController
{
    /** @return DataCollection<int, BookingRequestData> */
    public function requests(BookingRequestQueryData $data, ListBookingRequests $action): DataCollection
    {
        return $action->handle($data);
    }

    /** @return DataCollection<int, BookingRequestData> */
    public function today(ShowTodayAgenda $action): DataCollection
    {
        return $action->handle();
    }
}
