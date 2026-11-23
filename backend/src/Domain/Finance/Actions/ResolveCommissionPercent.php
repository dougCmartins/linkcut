<?php

declare(strict_types=1);

namespace Domain\Finance\Actions;

use Domain\Finance\Models\CommissionRule;

/**
 * Picks the effective percent for a service on a date: staff exception beats the general rule;
 * newest starts_on wins among ties. No rule → 0.
 */
final class ResolveCommissionPercent
{
    public function handle(int $serviceId, int $staffId, string $onDate): int
    {
        $staffRule = CommissionRule::query()
            ->where('service_id', $serviceId)
            ->where('staff_id', $staffId)
            ->where('starts_on', '<=', $onDate)
            ->orderByDesc('starts_on')
            ->orderByDesc('id')
            ->first();
        if ($staffRule !== null) {
            return (int) $staffRule->percent;
        }

        $general = CommissionRule::query()
            ->where('service_id', $serviceId)
            ->whereNull('staff_id')
            ->where('starts_on', '<=', $onDate)
            ->orderByDesc('starts_on')
            ->orderByDesc('id')
            ->first();

        return $general !== null ? (int) $general->percent : 0;
    }
}
