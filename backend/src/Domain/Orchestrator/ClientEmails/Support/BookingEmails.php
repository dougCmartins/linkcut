<?php

declare(strict_types=1);

namespace Domain\Orchestrator\ClientEmails\Support;

use Domain\Notification\Actions\SendBookingEmail;
use Domain\Notification\Data\BookingEmailData;
use Domain\Notification\Enums\BookingEmailKind;
use Domain\Orchestrator\Barbershop\Actions\ShowBarbershopContact;
use Domain\Schedule\Data\BookingRequestData;

/** Sends an e-mail about a booking of the current barbershop (skipped for old requests without e-mail). */
final class BookingEmails
{
    public function __construct(
        private readonly ShowBarbershopContact $showContact,
        private readonly SendBookingEmail $sendEmail,
    ) {}

    public function send(BookingRequestData $booking, BookingEmailKind $kind, ?string $registrationUrl = null, ?string $optOutUrl = null): void
    {
        if ($booking->client_email === '') {
            return;
        }
        $barbershop = $this->showContact->handle();

        $this->sendEmail->handle(new BookingEmailData(
            kind: $kind,
            client_name: $booking->client_name,
            client_email: $booking->client_email,
            service_name: $booking->service_name,
            date: $booking->date,
            time: $booking->starts_at,
            barbershop_name: $barbershop->name,
            barbershop_address: $barbershop->address,
            maps_url: $barbershop->maps_url,
            whatsapp_url: $barbershop->whatsapp_url,
            booking_url: $barbershop->booking_url,
            registration_url: $registrationUrl,
            opt_out_url: $optOutUrl,
        ));
    }
}
