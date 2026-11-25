<?php

declare(strict_types=1);

namespace Domain\Finance\Data;

use Domain\Finance\Support\PeriodWithinOneYear;
use Spatie\LaravelData\Attributes\MergeValidationRules;
use Spatie\LaravelData\Attributes\Validation\AfterOrEqual;
use Spatie\LaravelData\Attributes\Validation\DateFormat;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

#[MergeValidationRules]
final class CommissionReportQueryData extends Data
{
    public function __construct(
        #[Required, DateFormat('Y-m-d')]
        public readonly string $from,
        #[Required, DateFormat('Y-m-d'), AfterOrEqual('from')]
        public readonly string $to,
        #[Nullable, IntegerType, Min(1)]
        public readonly ?int $staff_id = null,
    ) {}

    /** @return array<string, list<object>> */
    public static function rules(): array
    {
        return ['to' => [new PeriodWithinOneYear]];
    }
}
