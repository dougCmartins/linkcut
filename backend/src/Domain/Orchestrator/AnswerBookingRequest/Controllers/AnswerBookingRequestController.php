<?php

declare(strict_types=1);

namespace Domain\Orchestrator\AnswerBookingRequest\Controllers;

use Domain\Orchestrator\AnswerBookingRequest\Actions\AcceptBooking;
use Domain\Orchestrator\AnswerBookingRequest\Actions\RejectBooking;
use Domain\Schedule\Data\BookingRequestData;
use Domain\Schedule\Data\BookingRequestIdData;

final class AnswerBookingRequestController
{
    public function accept(int $id, AcceptBooking $action): BookingRequestData
    {
        return $action->handle(BookingRequestIdData::from(BookingRequestIdData::validate(['id' => $id])));
    }

    public function reject(int $id, RejectBooking $action): BookingRequestData
    {
        return $action->handle(BookingRequestIdData::from(BookingRequestIdData::validate(['id' => $id])));
    }
}
