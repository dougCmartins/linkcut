<?php

declare(strict_types=1);

namespace Domain\Finance\Data;

use Domain\Finance\Support\PeriodWithinOneYear;
use Spatie\LaravelData\Attributes\MergeValidationRules;
use Spatie\LaravelData\Attributes\Validation\AfterOrEqual;
use Spatie\LaravelData\Attributes\Validation\DateFormat;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

/** Inclusive range of dates "Y-m-d", up to one year (the CSV report). */
#[MergeValidationRules]
final class PeriodData extends Data
{
    public function __construct(
        #[Required, DateFormat('Y-m-d')]
        public readonly string $from,
        #[Required, DateFormat('Y-m-d'), AfterOrEqual('from')]
        public readonly string $to,
    ) {}

    /** @return array<string, list<object>> */
    public static function rules(): array
    {
        return ['to' => [new PeriodWithinOneYear]];
    }
}
