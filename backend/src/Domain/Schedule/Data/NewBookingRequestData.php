<?php

declare(strict_types=1);

namespace Domain\Schedule\Data;

use Spatie\LaravelData\Data;

/** A request already checked by the public booking flow (service copied, phone as digits). */
final class NewBookingRequestData extends Data
{
    public function __construct(
        public readonly string $client_name,
        public readonly string $client_whatsapp,
        /** The first chosen service (the others are in `service_ids`). */
        public readonly int $service_id,
        /** @var list<int> every chosen service, in order */
        public readonly array $service_ids,
        /** "Corte + Barba" */
        public readonly string $service_name,
        public readonly int $duration_minutes,
        public readonly string $date,
        public readonly string $starts_at,
        public readonly string $client_email,
        /** The client account (global) behind this booking. */
        public readonly int $client_id,
        /** How far the client was from the barbershop (null = not shared). */
        public readonly ?int $client_distance_meters = null,
        /** Sum of the services' prices when booking (what the client saw). */
        public readonly ?int $price_in_cents = null,
        /** A note from the client to the barber. */
        public readonly ?string $client_notes = null,
    ) {}
}
