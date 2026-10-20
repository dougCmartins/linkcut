<?php

declare(strict_types=1);

namespace Domain\Finance\Models;

use Domain\Tenant\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** One concluded appointment in the history: what was done and what it cost at that moment. */
final class RevenueEntry extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'booking_request_id', 'date', 'time', 'client_name', 'service_name', 'price_in_cents', 'cost_in_cents',
    ];

    /** `date` stays a plain "Y-m-d" string (no time part, so date ranges compare correctly everywhere). */
    protected function casts(): array
    {
        return [
            'booking_request_id' => 'integer',
            'price_in_cents' => 'integer',
            'cost_in_cents' => 'integer',
        ];
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
        $query->orderByDesc('date')->orderByDesc('time')->orderByDesc('id');
    }
}
