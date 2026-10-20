<?php

declare(strict_types=1);

namespace Domain\Schedule\Actions;

use Domain\Schedule\Data\BookingRequestData;
use Domain\Schedule\Data\BookingRequestIdData;
use Domain\Schedule\Enums\BookingStatus;

/** The time becomes free again. */
final class RejectBookingRequest
{
    public function __construct(private readonly ChangeBookingStatus $changeStatus) {}

    public function handle(BookingRequestIdData $id): BookingRequestData
    {
        return BookingRequestData::from($this->changeStatus->handle($id, BookingStatus::Pending, BookingStatus::Rejected));
    }
}
