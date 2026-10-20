<?php

declare(strict_types=1);

namespace Domain\Finance\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

/** The "Despesas" screen: every fixed expense (by due day) and the one-off ones of the current month (newest first). */
final class ExpenseListData extends Data
{
    /**
     * @param  DataCollection<int, FixedExpenseData>  $fixed
     * @param  DataCollection<int, OneOffExpenseData>  $one_off
     */
    public function __construct(
        #[DataCollectionOf(FixedExpenseData::class)]
        public readonly DataCollection $fixed,
        #[DataCollectionOf(OneOffExpenseData::class)]
        public readonly DataCollection $one_off,
    ) {}
}
