<?php

declare(strict_types=1);

namespace Domain\Schedule\Controllers;

use Domain\Schedule\Actions\AcceptBookingRequest;
use Domain\Schedule\Actions\ListBookingRequests;
use Domain\Schedule\Actions\RejectBookingRequest;
use Domain\Schedule\Actions\ShowTodayAgenda;
use Domain\Schedule\Data\BookingRequestData;
use Domain\Schedule\Data\BookingRequestIdData;
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

    public function accept(int $id, AcceptBookingRequest $action): BookingRequestData
    {
        return $action->handle(BookingRequestIdData::from(BookingRequestIdData::validate(['id' => $id])));
    }

    public function reject(int $id, RejectBookingRequest $action): BookingRequestData
    {
        return $action->handle(BookingRequestIdData::from(BookingRequestIdData::validate(['id' => $id])));
    }
}
