<?php

declare(strict_types=1);

namespace Domain\Finance\Actions;

use Domain\Finance\Data\OneOffExpenseInputData;
use Domain\Tenant\Context\TenantClock;
use Illuminate\Validation\ValidationException;

/** A one-off expense is something already paid: its date can't be after today (tenant timezone). */
final class EnsureExpenseDateIsNotFuture
{
    public function __construct(private readonly TenantClock $clock) {}

    public function handle(OneOffExpenseInputData $data): void
    {
        if ($data->date > $this->clock->today()->toDateString()) {
            throw ValidationException::withMessages(['date' => 'before_or_equal']);
        }
    }
}
