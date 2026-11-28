<?php

declare(strict_types=1);

namespace Domain\Team\Actions;

use Domain\Team\Data\StaffMoveData;
use Domain\Team\Data\StaffMoveRecordData;
use Domain\Team\Enums\StaffMoveKind;
use Domain\Team\Enums\StaffMoveStatus;
use Domain\Team\Models\StaffMove;
use Domain\Team\Support\StaffMoveMapper;

/** Writes the move down (who, from where to where, which kind, since when). */
final class RecordStaffMove
{
    public function handle(StaffMoveRecordData $data): StaffMoveData
    {
        // A transfer of someone already moved closes the earlier move: one current move per person.
        StaffMove::query()->where('user_id', $data->user_id)->where('status', StaffMoveStatus::Active)
            ->update(['status' => StaffMoveStatus::Ended, 'ended_at' => now(), 'ended_by' => $data->created_by]);
        $move = StaffMove::query()->create([
            'organization_id' => $data->organization_id,
            'user_id' => $data->user_id,
            'from_staff_id' => $data->from_staff_id,
            'to_staff_id' => $data->to_staff_id,
            'kind' => $data->kind,
            'starts_on' => $data->starts_on,
            'ends_on' => $data->kind === StaffMoveKind::Loan ? $data->ends_on : null,
            'status' => StaffMoveStatus::Active,
            'created_by' => $data->created_by,
        ]);

        return StaffMoveMapper::toData($move);
    }
}
