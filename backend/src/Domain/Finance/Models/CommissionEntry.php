<?php

declare(strict_types=1);

namespace Domain\Finance\Models;

use Domain\Tenant\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** Snapshot of the commission calculation at completion time. */
final class CommissionEntry extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'booking_request_id', 'staff_id', 'date', 'base_in_cents', 'percent',
        'adjustments_json', 'amount_in_cents', 'closing_id',
    ];

    protected function casts(): array
    {
        return [
            'booking_request_id' => 'integer',
            'staff_id' => 'integer',
            'base_in_cents' => 'integer',
            'percent' => 'integer',
            'adjustments_json' => 'array',
            'amount_in_cents' => 'integer',
            'closing_id' => 'integer',
        ];
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeBetweenDates(Builder $query, string $from, string $to): void
    {
        $query->whereBetween('date', [$from, $to]);
    }
}
