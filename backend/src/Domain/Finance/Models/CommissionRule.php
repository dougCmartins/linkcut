<?php

declare(strict_types=1);

namespace Domain\Finance\Models;

use Domain\Tenant\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/** Percent rule for a service (optional staff exception). Starts_on: changes never rewrite the past. */
final class CommissionRule extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'service_id', 'staff_id', 'percent', 'starts_on',
    ];

    protected function casts(): array
    {
        return [
            'service_id' => 'integer',
            'staff_id' => 'integer',
            'percent' => 'integer',
        ];
    }
}
