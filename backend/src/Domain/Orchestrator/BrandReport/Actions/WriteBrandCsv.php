<?php

declare(strict_types=1);

namespace Domain\Orchestrator\BrandReport\Actions;

use Domain\Finance\Data\PeriodData;
use Domain\Orchestrator\BrandReport\Data\BrandReportData;
use Domain\Orchestrator\BrandReport\Data\BrandTotalsData;

/** CSV of the brand: one row per unit and a total row. Same Excel conventions as the history CSV. */
final class WriteBrandCsv
{
    private const BOM = "\xEF\xBB\xBF";

    private const FORMULA_START = '/^[=+\-@\t\r]/';

    public static function fileName(PeriodData $data): string
    {
        return "marca-linkcut_{$data->from}_a_{$data->to}.csv";
    }

    public function handle(BrandReportData $report): string
    {
        $output = fopen('php://temp', 'w+b');
        fwrite($output, self::BOM);
        $this->row($output, [
            __('finance.brand_csv.unit'),
            __('finance.brand_csv.revenue'),
            __('finance.brand_csv.count'),
            __('finance.brand_csv.average_ticket'),
            __('finance.brand_csv.commission'),
        ]);
        foreach ($report->units as $unit) {
            $this->row($output, [$unit->name, ...$this->money($unit->totals)]);
        }
        $this->row($output, [__('finance.brand_csv.total'), ...$this->money($report->total)]);

        rewind($output);
        $csv = (string) stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    /** @return list<string> */
    private function money(BrandTotalsData $totals): array
    {
        return [
            number_format($totals->revenue_in_cents / 100, 2, ',', ''),
            (string) $totals->count,
            number_format($totals->average_ticket_in_cents / 100, 2, ',', ''),
            number_format($totals->commission_in_cents / 100, 2, ',', ''),
        ];
    }

    /**
     * @param  resource  $output
     * @param  list<string>  $cells
     */
    private function row($output, array $cells): void
    {
        $safe = array_map(fn (string $cell): string => preg_match(self::FORMULA_START, $cell) ? "'{$cell}" : $cell, $cells);
        fputcsv($output, $safe, ';', '"', '', "\r\n");
    }
}
