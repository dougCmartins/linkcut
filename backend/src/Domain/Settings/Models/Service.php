<?php

declare(strict_types=1);

namespace Domain\Settings\Models;

use Domain\Tenant\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A service the client can book. The cost per appointment is private to the barber.
 * Removed services are soft deleted, so past booking requests still find them.
 */
final class Service extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    protected $fillable = ['tenant_id', 'name', 'duration_minutes', 'price_in_cents', 'cost_in_cents', 'position'];

    protected function casts(): array
    {
        return [
            'duration_minutes' => 'integer',
            'price_in_cents' => 'integer',
            'cost_in_cents' => 'integer',
            'position' => 'integer',
        ];
    }

    /** @param Builder<self> $query */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('position')->orderBy('id');
    }
}
