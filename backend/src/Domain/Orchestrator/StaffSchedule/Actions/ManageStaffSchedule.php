<?php

declare(strict_types=1);

namespace Domain\Orchestrator\StaffSchedule\Actions;

use Domain\Identity\Enums\UserRole;
use Domain\Identity\Models\User;
use Domain\Team\Actions\CreateStaffTimeBlock;
use Domain\Team\Actions\DeleteStaffTimeBlock;
use Domain\Team\Actions\FindStaffById;
use Domain\Team\Actions\ListStaffBusinessHours;
use Domain\Team\Actions\ListStaffServiceIds;
use Domain\Team\Actions\ListStaffTimeBlocks;
use Domain\Team\Actions\UpdateStaffBusinessHours;
use Domain\Team\Actions\UpdateStaffServices;
use Domain\Team\Data\CreateStaffTimeBlockData;
use Domain\Team\Data\StaffBusinessHourData;
use Domain\Team\Data\StaffIdData;
use Domain\Team\Data\StaffTimeBlockData;
use Domain\Team\Data\UpdateStaffHoursData;
use Domain\Team\Data\UpdateStaffServicesData;
use Domain\Team\Exceptions\OwnerRequiredException;
use Illuminate\Support\Facades\Auth;
use Spatie\LaravelData\DataCollection;

/** Owner-only CRUD for a barber's hours, services and time blocks. */
final class ManageStaffSchedule
{
    public function __construct(
        private readonly FindStaffById $findStaff,
        private readonly ListStaffBusinessHours $listHours,
        private readonly UpdateStaffBusinessHours $updateHoursAction,
        private readonly ListStaffServiceIds $listServices,
        private readonly UpdateStaffServices $updateServicesAction,
        private readonly ListStaffTimeBlocks $listBlocks,
        private readonly CreateStaffTimeBlock $createBlockAction,
        private readonly DeleteStaffTimeBlock $deleteBlockAction,
    ) {}

    /** @return DataCollection<int, StaffBusinessHourData> */
    public function hours(StaffIdData $staff): DataCollection
    {
        $this->ensureOwner();
        $this->findStaff->handle($staff);

        return $this->listHours->handle($staff);
    }

    /** @return DataCollection<int, StaffBusinessHourData> */
    public function updateHours(StaffIdData $staff, UpdateStaffHoursData $data): DataCollection
    {
        $this->ensureOwner();
        $this->findStaff->handle($staff);

        return $this->updateHoursAction->handle($staff, $data);
    }

    /** @return list<int> */
    public function services(StaffIdData $staff): array
    {
        $this->ensureOwner();
        $this->findStaff->handle($staff);

        return $this->listServices->handle($staff);
    }

    /** @return list<int> */
    public function updateServices(StaffIdData $staff, UpdateStaffServicesData $data): array
    {
        $this->ensureOwner();
        $this->findStaff->handle($staff);

        return $this->updateServicesAction->handle($staff, $data);
    }

    /** @return DataCollection<int, StaffTimeBlockData> */
    public function blocks(StaffIdData $staff): DataCollection
    {
        $this->ensureOwner();
        $this->findStaff->handle($staff);

        return $this->listBlocks->handle($staff);
    }

    public function createBlock(StaffIdData $staff, CreateStaffTimeBlockData $data): StaffTimeBlockData
    {
        $this->ensureOwner();
        $this->findStaff->handle($staff);

        return $this->createBlockAction->handle($staff, $data);
    }

    public function deleteBlock(StaffIdData $staff, int $blockId): void
    {
        $this->ensureOwner();
        $this->findStaff->handle($staff);
        $this->deleteBlockAction->handle($staff, $blockId);
    }

    private function ensureOwner(): void
    {
        /** @var User|null $user */
        $user = Auth::user();
        if ($user === null || $user->role !== UserRole::Owner) {
            throw new OwnerRequiredException;
        }
    }
}
