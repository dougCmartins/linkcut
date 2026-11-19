<?php

declare(strict_types=1);

namespace Domain\Team\Models;

use Domain\Tenant\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A barber of one unit (docs/decisoes/0001 P3). `user_id` is null until the invitation is accepted; the owner of the
 * barbershop is a barber of its own unit too.
 */
final class Staff extends Model
{
    use BelongsToTenant;

    protected $table = 'staff';

    protected $fillable = ['tenant_id', 'user_id', 'name', 'email', 'avatar_image_id', 'active'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    /** @return HasOne<StaffPermission, $this> */
    public function permissions(): HasOne
    {
        return $this->hasOne(StaffPermission::class);
    }
}
