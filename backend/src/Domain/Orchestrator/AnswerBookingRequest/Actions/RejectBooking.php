<?php

declare(strict_types=1);

namespace Domain\Orchestrator\AnswerBookingRequest\Actions;

use Domain\Notification\Enums\BookingEmailKind;
use Domain\Orchestrator\ClientEmails\Support\BookingEmails;
use Domain\Schedule\Actions\RejectBookingRequest;
use Domain\Schedule\Data\BookingRequestData;
use Domain\Schedule\Data\BookingRequestIdData;

/** The barber declines the request; the client gets an e-mail with a link to pick another time. */
final class RejectBooking
{
    public function __construct(
        private readonly RejectBookingRequest $reject,
        private readonly BookingEmails $emails,
    ) {}

    public function handle(BookingRequestIdData $id): BookingRequestData
    {
        $booking = $this->reject->handle($id);
        $this->emails->send($booking, BookingEmailKind::Rejected);

        return $booking;
    }
}
