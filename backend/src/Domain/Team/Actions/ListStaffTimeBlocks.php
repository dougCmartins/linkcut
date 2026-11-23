<?php

declare(strict_types=1);

namespace Domain\Team\Actions;

use Domain\Team\Data\StaffIdData;
use Domain\Team\Data\StaffTimeBlockData;
use Domain\Team\Models\StaffTimeBlock;
use Spatie\LaravelData\DataCollection;

final class ListStaffTimeBlocks
{
    /** @return DataCollection<int, StaffTimeBlockData> */
    public function handle(StaffIdData $data): DataCollection
    {
        $blocks = StaffTimeBlock::query()
            ->where('staff_id', $data->id)
            ->orderBy('starts_at')
            ->get()
            ->map(fn (StaffTimeBlock $block): StaffTimeBlockData => new StaffTimeBlockData(
                id: $block->id,
                staff_id: $block->staff_id,
                starts_at: $block->starts_at->format('Y-m-d H:i'),
                ends_at: $block->ends_at->format('Y-m-d H:i'),
                recurrence: $block->recurrence,
                weekdays: $block->weekdays,
                note: $block->note,
            ));

        return StaffTimeBlockData::collect($blocks, DataCollection::class);
    }
}
