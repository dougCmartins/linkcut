<?php

declare(strict_types=1);

namespace Domain\Orchestrator\StaffMove\Data;

use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\DateFormat;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

/** "Estender": the new last day of the loan. */
final class ExtendLoanData extends Data
{
    public function __construct(
        #[Required, Date, DateFormat('Y-m-d')]
        public readonly string $ends_on,
    ) {}
}
