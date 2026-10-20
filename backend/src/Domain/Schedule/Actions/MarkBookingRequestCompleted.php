<?php

declare(strict_types=1);

namespace Domain\Schedule\Actions;

use Domain\Schedule\Data\BookingRequestData;
use Domain\Schedule\Data\BookingRequestIdData;
use Domain\Schedule\Enums\BookingStatus;
use Domain\Schedule\Exceptions\BookingRequestNotFoundException;
use Domain\Schedule\Exceptions\InvalidStatusTransitionException;
use Domain\Schedule\Models\BookingRequest;
use Domain\Tenant\Context\TenantClock;

/** Only a confirmed appointment of today (tenant timezone) can be concluded. Called by CompleteAppointment. */
final class MarkBookingRequestCompleted
{
    public function __construct(
        private readonly ChangeBookingStatus $changeStatus,
        private readonly TenantClock $clock,
    ) {}

    public function handle(BookingRequestIdData $id): BookingRequestData
    {
        $request = BookingRequest::query()->find($id->id) ?? throw new BookingRequestNotFoundException;
        if ($request->date !== $this->clock->today()->toDateString()) {
            throw new InvalidStatusTransitionException;
        }

        return BookingRequestData::from($this->changeStatus->handle($id, BookingStatus::Confirmed, BookingStatus::Completed));
    }
}
