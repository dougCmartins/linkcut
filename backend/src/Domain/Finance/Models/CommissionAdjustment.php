<?php

declare(strict_types=1);

namespace Domain\Finance\Models;

use Domain\Finance\Enums\CommissionAppliesTo;
use Domain\Finance\Enums\CommissionKind;
use Domain\Finance\Enums\CommissionStage;
use Domain\Tenant\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/** Configurable deduction before or after the commission percent. */
final class CommissionAdjustment extends Model
{
    use BelongsToTenant;

    public const SYSTEM_SERVICE_COST = 'service_cost';

    protected $fillable = [
        'tenant_id', 'staff_id', 'name', 'stage', 'kind', 'applies_to', 'value', 'active', 'starts_on', 'system_key',
    ];

    protected function casts(): array
    {
        return [
            'staff_id' => 'integer',
            'stage' => CommissionStage::class,
            'kind' => CommissionKind::class,
            'applies_to' => CommissionAppliesTo::class,
            'value' => 'integer',
            'active' => 'boolean',
        ];
    }
}
