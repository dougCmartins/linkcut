<?php

declare(strict_types=1);

namespace Domain\Orchestrator\AnswerBookingRequest\Actions;

use Domain\Notification\Enums\BookingEmailKind;
use Domain\Orchestrator\ClientEmails\Support\BookingEmails;
use Domain\Schedule\Actions\AcceptBookingRequest;
use Domain\Schedule\Data\BookingRequestData;
use Domain\Schedule\Data\BookingRequestIdData;

/** The barber confirms the request; the client gets the "confirmed" e-mail. */
final class AcceptBooking
{
    public function __construct(
        private readonly AcceptBookingRequest $accept,
        private readonly BookingEmails $emails,
    ) {}

    public function handle(BookingRequestIdData $id): BookingRequestData
    {
        $booking = $this->accept->handle($id);
        $this->emails->send($booking, BookingEmailKind::Accepted);

        return $booking;
    }
}
