<?php

declare(strict_types=1);

namespace Domain\Orchestrator\CommissionReport\Data;

use Spatie\LaravelData\Data;

final class CommissionReportRequestedData extends Data
{
    public function __construct(
        public readonly string $email,
    ) {}
}
