<?php

declare(strict_types=1);

namespace Domain\Schedule\Actions;

use Domain\Schedule\Data\BookingRequestIdData;
use Domain\Schedule\Enums\BookingStatus;
use Domain\Schedule\Exceptions\BookingRequestNotFoundException;
use Domain\Schedule\Exceptions\InvalidStatusTransitionException;
use Domain\Schedule\Models\BookingRequest;
use Illuminate\Support\Facades\Log;

/** Moves a request from one status to another; anything but the expected current status is refused. */
final class ChangeBookingStatus
{
    public function handle(BookingRequestIdData $id, BookingStatus $from, BookingStatus $to): BookingRequest
    {
        $request = BookingRequest::query()->lockForUpdate()->find($id->id) ?? throw new BookingRequestNotFoundException;
        if ($request->status !== $from) {
            throw new InvalidStatusTransitionException;
        }

        $request->update([
            'status' => $to,
            'completed_at' => $to === BookingStatus::Completed ? now() : null,
        ]);
        Log::info('schedule_booking_status_changed', ['booking_request_id' => $request->id, 'from' => $from->value, 'to' => $to->value]);

        return $request;
    }
}
