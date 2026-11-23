<?php

declare(strict_types=1);

namespace Domain\Finance\Actions;

use Domain\Finance\Data\CommissionCalculationData;
use Domain\Finance\Support\CommissionCalculator;

/**
 * Resolves percent(s) and active adjustments, then runs the pure calculator.
 * Several services: frozen price is split by catalog weights; percents applied per line; one snapshot.
 *
 * @param  list<array{service_id: int, catalog_price_in_cents: int}>  $serviceLines
 */
final class CalculateCommission
{
    public function __construct(
        private readonly ResolveCommissionPercent $resolvePercent,
        private readonly ListActiveCommissionAdjustments $listAdjustments,
        private readonly CommissionCalculator $calculator,
    ) {}

    public function handle(
        int $priceInCents,
        int $costInCents,
        int $staffId,
        string $onDate,
        array $serviceLines,
    ): CommissionCalculationData {
        $percent = $this->effectivePercent($serviceLines, $staffId, $onDate);
        $adjustments = $this->listAdjustments->handle($staffId, $onDate);

        return $this->calculator->calculate($priceInCents, $percent, $adjustments, $costInCents);
    }

    /**
     * @param  list<array{service_id: int, catalog_price_in_cents: int}>  $serviceLines
     */
    private function effectivePercent(array $serviceLines, int $staffId, string $onDate): int
    {
        if ($serviceLines === []) {
            return 0;
        }
        if (count($serviceLines) === 1) {
            return $this->resolvePercent->handle($serviceLines[0]['service_id'], $staffId, $onDate);
        }

        $weights = array_map(fn (array $line): int => max(0, $line['catalog_price_in_cents']), $serviceLines);
        $weightSum = array_sum($weights);
        if ($weightSum <= 0) {
            $weights = array_fill(0, count($serviceLines), 1);
            $weightSum = count($serviceLines);
        }

        $weighted = 0.0;
        foreach ($serviceLines as $i => $line) {
            $p = $this->resolvePercent->handle($line['service_id'], $staffId, $onDate);
            $weighted += $p * ($weights[$i] / $weightSum);
        }

        // Keep tenths as int; no final money round here — only weights.
        return (int) round($weighted, 0, PHP_ROUND_HALF_UP);
    }
}
