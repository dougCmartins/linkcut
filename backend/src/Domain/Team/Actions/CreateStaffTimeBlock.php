<?php

declare(strict_types=1);

namespace Domain\Team\Actions;

use Carbon\CarbonImmutable;
use Domain\Team\Data\CreateStaffTimeBlockData;
use Domain\Team\Data\StaffIdData;
use Domain\Team\Data\StaffTimeBlockData;
use Domain\Team\Enums\StaffTimeBlockRecurrence;
use Domain\Team\Exceptions\StaffNotFoundException;
use Domain\Team\Models\Staff;
use Domain\Team\Models\StaffTimeBlock;
use Illuminate\Validation\ValidationException;

final class CreateStaffTimeBlock
{
    public function handle(StaffIdData $staff, CreateStaffTimeBlockData $data): StaffTimeBlockData
    {
        $member = Staff::query()->whereKey($staff->id)->first() ?? throw new StaffNotFoundException;

        if ($data->recurrence === StaffTimeBlockRecurrence::Weekly && ($data->weekdays === null || $data->weekdays === [])) {
            throw ValidationException::withMessages(['weekdays' => 'required']);
        }

        $block = StaffTimeBlock::query()->create([
            'staff_id' => $member->id,
            'starts_at' => CarbonImmutable::createFromFormat('Y-m-d H:i', $data->starts_at),
            'ends_at' => CarbonImmutable::createFromFormat('Y-m-d H:i', $data->ends_at),
            'recurrence' => $data->recurrence,
            'weekdays' => $data->recurrence === StaffTimeBlockRecurrence::Weekly ? array_values($data->weekdays ?? []) : null,
            'note' => $data->note !== null ? trim($data->note) : null,
        ]);

        return new StaffTimeBlockData(
            id: $block->id,
            staff_id: $block->staff_id,
            starts_at: $block->starts_at->format('Y-m-d H:i'),
            ends_at: $block->ends_at->format('Y-m-d H:i'),
            recurrence: $block->recurrence,
            weekdays: $block->weekdays,
            note: $block->note,
        );
    }
}
