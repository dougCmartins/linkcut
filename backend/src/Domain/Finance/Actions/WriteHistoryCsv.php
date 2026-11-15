<?php

declare(strict_types=1);

namespace Domain\Finance\Actions;

use Carbon\CarbonImmutable;
use Domain\Finance\Data\PeriodData;
use Domain\Finance\Models\RevenueEntry;

/**
 * CSV of the whole period, for Excel in Portuguese: ";" separator, decimal comma, UTF-8 BOM, CRLF, and cells that
 * would run as formulas are neutralized. Rows are read in chunks (lazy), so a long year stays light on memory.
 * Runs inside the barbershop's tenant (the report job sets it).
 */
final class WriteHistoryCsv
{
    private const BOM = "\xEF\xBB\xBF";

    private const FORMULA_START = '/^[=+\-@\t\r]/';

    /** The file name the barber sees in the e-mail. */
    public static function fileName(PeriodData $data): string
    {
        return "historico-linkcut_{$data->from}_a_{$data->to}.csv";
    }

    public function handle(PeriodData $data): string
    {
        $output = fopen('php://temp', 'w+b');
        fwrite($output, self::BOM);
        $this->row($output, [
            __('finance.csv.date'), __('finance.csv.time'), __('finance.csv.client'), __('finance.csv.service'), __('finance.csv.value'),
        ]);

        RevenueEntry::query()->betweenDates($data->from, $data->to)->newestFirst()->lazy()->each(
            fn (RevenueEntry $entry) => $this->row($output, [
                CarbonImmutable::parse($entry->date)->format('d/m/Y'),
                $entry->time,
                $entry->client_name,
                $entry->service_name,
                number_format($entry->price_in_cents / 100, 2, ',', ''),
            ]),
        );
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
