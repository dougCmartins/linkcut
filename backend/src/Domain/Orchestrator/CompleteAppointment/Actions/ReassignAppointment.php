<?php

declare(strict_types=1);

namespace Domain\Orchestrator\CompleteAppointment\Actions;

use Domain\Identity\Enums\UserRole;
use Domain\Identity\Models\User;
use Domain\Schedule\Actions\ReassignBookingRequest;
use Domain\Schedule\Data\BookingRequestData;
use Domain\Schedule\Data\BookingRequestIdData;
use Domain\Team\Actions\FindStaffById;
use Domain\Team\Data\StaffIdData;
use Domain\Team\Exceptions\OwnerRequiredException;
use Illuminate\Support\Facades\Auth;

/** Owner moves an appointment to another barber of the same unit. */
final class ReassignAppointment
{
    public function __construct(
        private readonly ReassignBookingRequest $reassign,
        private readonly FindStaffById $findStaff,
    ) {}

    public function handle(BookingRequestIdData $id, StaffIdData $staff): BookingRequestData
    {
        /** @var User|null $user */
        $user = Auth::user();
        if ($user === null || $user->role !== UserRole::Owner) {
            throw new OwnerRequiredException;
        }

        $this->findStaff->handle($staff);

        return $this->reassign->handle($id, $staff->id);
    }
}
