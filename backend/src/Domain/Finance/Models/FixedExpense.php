<?php

declare(strict_types=1);

namespace Domain\Finance\Models;

use Carbon\CarbonImmutable;
use Domain\Tenant\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/** A cost that repeats every month (chair rent, internet, DAS/MEI). Removed ones can be restored. */
final class FixedExpense extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    protected $fillable = ['tenant_id', 'name', 'amount_in_cents', 'due_day'];

    protected function casts(): array
    {
        return ['amount_in_cents' => 'integer', 'due_day' => 'integer'];
    }

    /** @param Builder<self> $query */
    public function scopeByDueDay(Builder $query): void
    {
        $query->orderBy('due_day')->orderBy('id');
    }

    /** Counts in full from its due day on (31 falls on the 30th in a 30-day month). */
    public function isDueOn(CarbonImmutable $day): bool
    {
        return min($this->due_day, $day->daysInMonth) <= $day->day;
    }
}
