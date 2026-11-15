<?php

declare(strict_types=1);

namespace Domain\Orchestrator\HistoryReport\Data;

use Spatie\LaravelData\Data;

/** The report is on its way: the (masked) e-mail it goes to. */
final class HistoryReportRequestedData extends Data
{
    public function __construct(
        public readonly string $email,
    ) {}
}
