<?php

declare(strict_types=1);

namespace Domain\Team\Models;

use Domain\Tenant\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/** Opening hours of one weekday for one barber (0 = Sunday … 6 = Saturday). */
final class StaffBusinessHour extends Model
{
    use BelongsToTenant;

    protected $table = 'staff_business_hours';

    protected $fillable = ['tenant_id', 'staff_id', 'weekday', 'is_open', 'opens_at', 'closes_at'];

    protected function casts(): array
    {
        return [
            'staff_id' => 'integer',
            'weekday' => 'integer',
            'is_open' => 'boolean',
        ];
    }
}
