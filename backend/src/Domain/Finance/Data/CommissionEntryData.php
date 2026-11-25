<?php

declare(strict_types=1);

namespace Domain\Finance\Data;

use Spatie\LaravelData\Data;

final class CommissionEntryData extends Data
{
    /**
     * @param  list<array<string, mixed>>  $adjustments
     */
    public function __construct(
        public readonly int $id,
        public readonly int $booking_request_id,
        public readonly int $staff_id,
        public readonly string $date,
        public readonly int $base_in_cents,
        public readonly int $percent,
        public readonly array $adjustments,
        public readonly int $amount_in_cents,
        public readonly ?int $closing_id,
    ) {}
}
