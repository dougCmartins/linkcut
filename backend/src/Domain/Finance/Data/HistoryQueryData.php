<?php

declare(strict_types=1);

namespace Domain\Finance\Data;

use Spatie\LaravelData\Attributes\Validation\AfterOrEqual;
use Spatie\LaravelData\Attributes\Validation\DateFormat;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

/** GET /finance/history?from&to&page&per_page */
final class HistoryQueryData extends Data
{
    public function __construct(
        #[Required, DateFormat('Y-m-d')]
        public readonly string $from,
        #[Required, DateFormat('Y-m-d'), AfterOrEqual('from')]
        public readonly string $to,
        #[IntegerType, Min(1)]
        public readonly int $page = 1,
        #[IntegerType, In([50, 100, 200])]
        public readonly int $per_page = 50,
    ) {}
}
