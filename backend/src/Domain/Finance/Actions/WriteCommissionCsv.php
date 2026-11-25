<?php

declare(strict_types=1);

namespace Domain\Finance\Actions;

use Carbon\CarbonImmutable;
use Domain\Finance\Data\CommissionReportQueryData;
use Domain\Finance\Models\CommissionEntry;

/** CSV of commission entries for the period (same Excel conventions as history). */
final class WriteCommissionCsv
{
    private const BOM = "\xEF\xBB\xBF";

    private const FORMULA_START = '/^[=+\-@\t\r]/';

    public static function fileName(CommissionReportQueryData $data): string
    {
        return "comissoes-linkcut_{$data->from}_a_{$data->to}.csv";
    }

    public function handle(CommissionReportQueryData $data): string
    {
        $output = fopen('php://temp', 'w+b');
        fwrite($output, self::BOM);
        $this->row($output, [
            __('finance.csv.date'),
            __('finance.commission_csv.staff_id'),
            __('finance.commission_csv.percent'),
            __('finance.commission_csv.base'),
            __('finance.commission_csv.amount'),
            __('finance.commission_csv.booking_id'),
        ]);

        $q = CommissionEntry::query()->betweenDates($data->from, $data->to)->orderBy('date')->orderBy('id');
        if ($data->staff_id !== null) {
            $q->where('staff_id', $data->staff_id);
        }

        $q->lazy()->each(fn (CommissionEntry $entry) => $this->row($output, [
            CarbonImmutable::parse($entry->date)->format('d/m/Y'),
            (string) $entry->staff_id,
            number_format($entry->percent / 10, 1, ',', ''),
            number_format($entry->base_in_cents / 100, 2, ',', ''),
            number_format($entry->amount_in_cents / 100, 2, ',', ''),
            (string) $entry->booking_request_id,
        ]));

        rewind($output);
        $csv = (string) stream_get_contents($output);
        fclose($output);

        return $csv;
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
