<?php

declare(strict_types=1);

namespace Domain\Schedule\Data;

use Domain\Schedule\Enums\BookingStatus;
use Spatie\LaravelData\Data;

/** A booking request as the barber sees it (private: has the client's WhatsApp). */
final class BookingRequestData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly string $client_name,
        public readonly string $client_whatsapp,
        public readonly int $service_id,
        public readonly string $service_name,
        public readonly int $duration_minutes,
        public readonly string $date,
        public readonly string $starts_at,
        public readonly string $ends_at,
        public readonly BookingStatus $status,
        /** How far the client was from the barbershop when booking (null = not shared). */
        public readonly ?int $client_distance_meters = null,
    ) {}
}
