<?php

declare(strict_types=1);

namespace Domain\Team\Models;

use Domain\Team\Enums\StaffTimeBlockRecurrence;
use Domain\Tenant\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/** A one-off or weekly recurring block on a barber's agenda. */
final class StaffTimeBlock extends Model
{
    use BelongsToTenant;

    protected $table = 'staff_time_blocks';

    protected $fillable = [
        'tenant_id',
        'staff_id',
        'starts_at',
        'ends_at',
        'recurrence',
        'weekdays',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'staff_id' => 'integer',
            'starts_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',
            'recurrence' => StaffTimeBlockRecurrence::class,
            'weekdays' => 'array',
        ];
    }
}
