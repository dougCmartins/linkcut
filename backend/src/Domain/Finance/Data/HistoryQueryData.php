<?php

declare(strict_types=1);

namespace Domain\Finance\Data;

use Domain\Finance\Support\PeriodWithinOneYear;
use Spatie\LaravelData\Attributes\MergeValidationRules;
use Spatie\LaravelData\Attributes\Validation\AfterOrEqual;
use Spatie\LaravelData\Attributes\Validation\DateFormat;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

/** GET /finance/history?from&to&cursor: up to one year; "cursor" (from the previous answer) asks for the next rows. */
#[MergeValidationRules]
final class HistoryQueryData extends Data
{
    public function __construct(
        #[Required, DateFormat('Y-m-d')]
        public readonly string $from,
        #[Required, DateFormat('Y-m-d'), AfterOrEqual('from')]
        public readonly string $to,
        #[Nullable, StringType, Max(500)]
        public readonly ?string $cursor = null,
    ) {}

    /** @return array<string, list<object>> */
    public static function rules(): array
    {
        return ['to' => [new PeriodWithinOneYear]];
    }
}
