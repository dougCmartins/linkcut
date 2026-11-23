<?php

declare(strict_types=1);

namespace Domain\Finance\Data;

use Spatie\LaravelData\Data;

/**
 * @param  list<array{service_id: int, catalog_price_in_cents: int}>  $service_lines
 */
final class RecordCommissionData extends Data
{
    public function __construct(
        public readonly int $booking_request_id,
        public readonly int $staff_id,
        public readonly string $date,
        public readonly int $price_in_cents,
        public readonly int $cost_in_cents,
        public readonly array $service_lines,
    ) {}
}
