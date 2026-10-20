<?php

declare(strict_types=1);

namespace Domain\Notification\Data;

use Domain\Notification\Enums\BookingEmailKind;
use Spatie\LaravelData\Data;

/** Everything a booking e-mail shows; built by the orchestrator (Schedule + Settings + Client). */
final class BookingEmailData extends Data
{
    public function __construct(
        public readonly BookingEmailKind $kind,
        public readonly string $client_name,
        public readonly string $client_email,
        public readonly string $service_name,
        /** "Y-m-d" and "H:i", in the barbershop's timezone. */
        public readonly string $date,
        public readonly string $time,
        public readonly string $barbershop_name,
        public readonly string $barbershop_address,
        public readonly string $maps_url,
        public readonly string $whatsapp_url,
        /** The barbershop's public page (to book again). */
        public readonly string $booking_url,
        /** Only while the client has no password: the "Finalizar cadastro" button. */
        public readonly ?string $registration_url = null,
        /** Only on reminders: "Não quero receber lembretes". */
        public readonly ?string $opt_out_url = null,
    ) {}
}
