<?php

declare(strict_types=1);

namespace Domain\Orchestrator\StaffSchedule\Controllers;

use Domain\Orchestrator\StaffSchedule\Actions\ManageStaffSchedule;
use Domain\Team\Data\CreateStaffTimeBlockData;
use Domain\Team\Data\StaffBusinessHourData;
use Domain\Team\Data\StaffIdData;
use Domain\Team\Data\StaffTimeBlockData;
use Domain\Team\Data\UpdateStaffHoursData;
use Domain\Team\Data\UpdateStaffServicesData;
use Illuminate\Http\Response;
use Spatie\LaravelData\DataCollection;

final class StaffScheduleController
{
    /** @return DataCollection<int, StaffBusinessHourData> */
    public function hours(int $id, ManageStaffSchedule $action): DataCollection
    {
        return $action->hours(self::staff($id));
    }

    /** @return DataCollection<int, StaffBusinessHourData> */
    public function updateHours(int $id, UpdateStaffHoursData $data, ManageStaffSchedule $action): DataCollection
    {
        return $action->updateHours(self::staff($id), $data);
    }

    /** @return array{service_ids: list<int>} */
    public function services(int $id, ManageStaffSchedule $action): array
    {
        return ['service_ids' => $action->services(self::staff($id))];
    }

    /** @return array{service_ids: list<int>} */
    public function updateServices(int $id, UpdateStaffServicesData $data, ManageStaffSchedule $action): array
    {
        return ['service_ids' => $action->updateServices(self::staff($id), $data)];
    }

    /** @return DataCollection<int, StaffTimeBlockData> */
    public function blocks(int $id, ManageStaffSchedule $action): DataCollection
    {
        return $action->blocks(self::staff($id));
    }

    public function storeBlock(int $id, CreateStaffTimeBlockData $data, ManageStaffSchedule $action): StaffTimeBlockData
    {
        return $action->createBlock(self::staff($id), $data);
    }

    public function destroyBlock(int $id, int $blockId, ManageStaffSchedule $action): Response
    {
        $action->deleteBlock(self::staff($id), $blockId);

        return response()->noContent();
    }

    private static function staff(int $id): StaffIdData
    {
        return StaffIdData::from(StaffIdData::validate(['id' => $id]));
    }
}
