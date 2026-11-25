<?php

declare(strict_types=1);

namespace Domain\Orchestrator\BrandReport\Data;

use Spatie\LaravelData\Data;

/** Revenue and commissions of a period: one unit, or the whole brand. */
final class BrandTotalsData extends Data
{
    public function __construct(
        public readonly int $revenue_in_cents,
        public readonly int $count,
        public readonly int $average_ticket_in_cents,
        public readonly int $commission_in_cents,
    ) {}

    public static function of(int $revenueInCents, int $count, int $commissionInCents): self
    {
        return new self(
            revenue_in_cents: $revenueInCents,
            count: $count,
            average_ticket_in_cents: $count > 0 ? (int) round($revenueInCents / $count) : 0,
            commission_in_cents: $commissionInCents,
        );
    }
}
