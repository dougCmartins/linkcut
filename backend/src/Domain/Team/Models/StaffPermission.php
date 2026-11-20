<?php

declare(strict_types=1);

namespace Domain\Team\Models;

use Domain\Tenant\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/** What the owner lets a barber see. Everything starts off: own agenda and own appointments, without values. */
final class StaffPermission extends Model
{
    use BelongsToTenant;

    public const KEYS = ['see_prices', 'see_own_commission', 'see_unit_agenda'];

    protected $fillable = ['tenant_id', 'staff_id', 'see_prices', 'see_own_commission', 'see_unit_agenda'];

    protected function casts(): array
    {
        return ['see_prices' => 'boolean', 'see_own_commission' => 'boolean', 'see_unit_agenda' => 'boolean'];
    }
}
