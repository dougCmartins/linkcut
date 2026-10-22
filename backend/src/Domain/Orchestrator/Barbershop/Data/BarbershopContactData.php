<?php

declare(strict_types=1);

namespace Domain\Orchestrator\Barbershop\Data;

use Spatie\LaravelData\Data;

/** How a client finds and reaches a barbershop (e-mails and the client area). */
final class BarbershopContactData extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly string $slug,
        /** One line, e.g. "Rua Augusta, 1500 – Consolação, São Paulo/SP" (empty without an address). */
        public readonly string $address,
        public readonly string $maps_url,
        /** wa.me link of the barbershop (empty without a number). */
        public readonly string $whatsapp_url,
        /** The public page (book again). */
        public readonly string $booking_url,
        /** The barbershop's photo or logo (null = none: the SPA shows its initials). */
        public readonly ?string $avatar_url = null,
    ) {}
}
