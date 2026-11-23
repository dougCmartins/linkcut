<?php

declare(strict_types=1);

namespace Domain\Team\Models;

use Domain\Tenant\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/** A barber of one unit (tenant). user_id is null until the invite is accepted. */
final class Staff extends Model
{
    use BelongsToTenant;

    protected $table = 'staff';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'name',
        'avatar',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    /** @return HasOne<StaffPermission, $this> */
    public function permissions(): HasOne
    {
        return $this->hasOne(StaffPermission::class);
    }

    /** @return HasMany<StaffInvite, $this> */
    public function invites(): HasMany
    {
        return $this->hasMany(StaffInvite::class);
    }
}
