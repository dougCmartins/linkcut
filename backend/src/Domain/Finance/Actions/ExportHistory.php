<?php

declare(strict_types=1);

namespace Domain\Finance\Actions;

use Carbon\CarbonImmutable;
use Domain\Finance\Data\PeriodData;
use Domain\Finance\Models\RevenueEntry;
use Domain\Tenant\Context\CurrentTenant;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * CSV of the whole period, for Excel in Portuguese: ";" separator, decimal comma, UTF-8 BOM, CRLF,
 * and cells that would run as formulas are neutralized. Streamed row by row (no size limit in memory).
 */
final class ExportHistory
{
    private const BOM = "\xEF\xBB\xBF";

    private const FORMULA_START = '/^[=+\-@\t\r]/';

    public function __construct(private readonly CurrentTenant $currentTenant) {}

    public function handle(PeriodData $data): StreamedResponse
    {
        $tenantId = $this->currentTenant->id();
        Log::info('finance_history_exported', ['from' => $data->from, 'to' => $data->to]);

        return response()->streamDownload(function () use ($data, $tenantId): void {
            $this->currentTenant->run($tenantId, fn () => $this->write($data));
        }, "historico-linkcut_{$data->from}_a_{$data->to}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function write(PeriodData $data): void
    {
        $output = fopen('php://output', 'wb');
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
        fclose($output);
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
