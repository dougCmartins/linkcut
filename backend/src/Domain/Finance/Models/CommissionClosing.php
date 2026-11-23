<?php

declare(strict_types=1);

namespace Domain\Finance\Models;

use Domain\Tenant\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Period lock: closed appointments cannot be undone until the owner reopens. */
final class CommissionClosing extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'from', 'to', 'closed_at', 'closed_by', 'reopened_at',
    ];

    protected function casts(): array
    {
        return [
            'closed_at' => 'datetime',
            'reopened_at' => 'datetime',
            'closed_by' => 'integer',
        ];
    }

    /** @return HasMany<CommissionEntry, $this> */
    public function entries(): HasMany
    {
        return $this->hasMany(CommissionEntry::class, 'closing_id');
    }
}
