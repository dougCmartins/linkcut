<?php

declare(strict_types=1);

namespace Domain\Schedule\Actions;

use Domain\Schedule\Data\BookingRequestData;
use Domain\Schedule\Data\BookingRequestIdData;
use Domain\Schedule\Enums\BookingStatus;
use Domain\Schedule\Exceptions\InvalidStatusTransitionException;

/** The client gave up the time (waiting or confirmed): it becomes free again. Runs inside the booking's tenant. */
final class CancelBookingRequest
{
    public function __construct(private readonly ChangeBookingStatus $changeStatus) {}

    public function handle(BookingRequestIdData $id, BookingStatus $current): BookingRequestData
    {
        if (! $current->isCancellableByClient()) {
            throw new InvalidStatusTransitionException;
        }

        return BookingRequestData::from($this->changeStatus->handle($id, $current, BookingStatus::Cancelled));
    }
}
