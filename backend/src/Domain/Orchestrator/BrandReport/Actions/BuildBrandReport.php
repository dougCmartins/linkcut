<?php

declare(strict_types=1);

namespace Domain\Orchestrator\BrandReport\Actions;

use Domain\Finance\Data\PeriodData;
use Domain\Finance\Models\CommissionEntry;
use Domain\Finance\Models\RevenueEntry;
use Domain\Orchestrator\BrandReport\Data\BrandReportData;
use Domain\Orchestrator\BrandReport\Data\BrandTotalsData;
use Domain\Orchestrator\BrandReport\Data\BrandUnitReportData;
use Domain\Tenant\Models\Tenant;

/**
 * Revenue, history and commissions summed over every unit of one organization. This is the only place that reads
 * across tenants, on purpose: the organization id is given by the caller (already checked as the brand owner), and the
 * work is three queries however many units there are (grouped by unit, never one query per unit).
 */
final class BuildBrandReport
{
    public function handle(int $organizationId, PeriodData $period): BrandReportData
    {
        $units = Tenant::query()
            ->where('organization_id', $organizationId)
            ->orderByDesc('is_main')
            ->orderBy('id')
            ->get(['id', 'name', 'slug', 'is_main']);
        $unitIds = $units->pluck('id')->all();

        $revenue = RevenueEntry::withoutTenantScope()
            ->whereIn('tenant_id', $unitIds)
            ->betweenDates($period->from, $period->to)
            ->selectRaw('tenant_id, count(*) as entries, coalesce(sum(price_in_cents), 0) as total')
            ->groupBy('tenant_id')
            ->toBase()
            ->get()
            ->keyBy('tenant_id');
        $commissions = CommissionEntry::withoutTenantScope()
            ->whereIn('tenant_id', $unitIds)
            ->betweenDates($period->from, $period->to)
            ->selectRaw('tenant_id, coalesce(sum(amount_in_cents), 0) as total')
            ->groupBy('tenant_id')
            ->toBase()
            ->get()
            ->keyBy('tenant_id');

        $rows = $units->map(function (Tenant $unit) use ($revenue, $commissions): BrandUnitReportData {
            return new BrandUnitReportData(
                unit_id: $unit->id,
                name: $unit->name,
                slug: $unit->slug,
                is_main: $unit->is_main,
                totals: BrandTotalsData::of(
                    (int) ($revenue[$unit->id]->total ?? 0),
                    (int) ($revenue[$unit->id]->entries ?? 0),
                    (int) ($commissions[$unit->id]->total ?? 0),
                ),
            );
        })->values()->all();

        return new BrandReportData(
            from: $period->from,
            to: $period->to,
            total: BrandTotalsData::of(
                array_sum(array_map(fn (BrandUnitReportData $row): int => $row->totals->revenue_in_cents, $rows)),
                array_sum(array_map(fn (BrandUnitReportData $row): int => $row->totals->count, $rows)),
                array_sum(array_map(fn (BrandUnitReportData $row): int => $row->totals->commission_in_cents, $rows)),
            ),
            units: $rows,
        );
    }
}
