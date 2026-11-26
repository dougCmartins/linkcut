<?php

declare(strict_types=1);

namespace Domain\Team\Models;

use Domain\Team\Enums\StaffMoveKind;
use Domain\Team\Enums\StaffMoveStatus;
use Illuminate\Database\Eloquent\Model;

/**
 * A barber moved between two units of one brand (docs/decisoes/0003). Not tenant-scoped: it links a staff row of the
 * origin and one of the destination, so it sits at the brand level (always read with the organization).
 */
final class StaffMove extends Model
{
    protected $fillable = [
        'organization_id', 'user_id', 'from_staff_id', 'to_staff_id', 'kind', 'starts_on', 'ends_on', 'status',
        'created_by', 'ended_at', 'ended_by',
    ];

    protected function casts(): array
    {
        return [
            'kind' => StaffMoveKind::class,
            'status' => StaffMoveStatus::class,
            'starts_on' => 'immutable_date',
            'ends_on' => 'immutable_date',
            'ended_at' => 'immutable_datetime',
        ];
    }
}
