<?php

declare(strict_types=1);

namespace Domain\Finance\Models;

use Domain\Tenant\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/** A cost paid once (supplies, a new clipper). Removed ones can be restored. */
final class OneOffExpense extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    protected $fillable = ['tenant_id', 'description', 'amount_in_cents', 'date'];

    protected function casts(): array
    {
        // `date` stays a plain "Y-m-d" string (no time part, so date ranges compare correctly everywhere).
        return ['amount_in_cents' => 'integer'];
    }

    /**
     * Inclusive range of dates "Y-m-d".
     *
     * @param  Builder<self>  $query
     */
    public function scopeBetweenDates(Builder $query, string $from, string $to): void
    {
        $query->whereBetween('date', [$from, $to]);
    }

    /** @param Builder<self> $query */
    public function scopeNewestFirst(Builder $query): void
    {
        $query->orderByDesc('date')->orderByDesc('id');
    }
}
