<?php

declare(strict_types=1);

namespace Domain\Orchestrator\CompleteAppointment\Actions;

use Carbon\CarbonImmutable;
use Domain\Identity\Enums\UserRole;
use Domain\Identity\Models\User;
use Domain\Schedule\Actions\ReassignBookingRequest;
use Domain\Schedule\Data\BookingRequestData;
use Domain\Schedule\Data\BookingRequestIdData;
use Domain\Schedule\Exceptions\SlotUnavailableException;
use Domain\Team\Actions\FindStaffById;
use Domain\Team\Actions\ListStaffBlockRangesForDate;
use Domain\Team\Actions\ListStaffBusinessHours;
use Domain\Team\Data\StaffBusinessHourData;
use Domain\Team\Data\StaffIdData;
use Domain\Team\Exceptions\OwnerRequiredException;
use Domain\Team\Exceptions\StaffServiceUnavailableException;
use Domain\Team\Support\StaffServiceCoverage;
use Illuminate\Support\Facades\Auth;

/**
 * Owner moves an appointment to another barber of the same unit (decision 0002). The other barber must do every
 * service of it (else STAFF_SERVICE_UNAVAILABLE) and be free then: inside their working hours, outside their break,
 * blocks and other appointments (else SLOT_UNAVAILABLE). Only pending or confirmed appointments move.
 */
final class ReassignAppointment
{
    public function __construct(
        private readonly ReassignBookingRequest $reassign,
        private readonly FindStaffById $findStaff,
        private readonly ListStaffBusinessHours $listHours,
        private readonly ListStaffBlockRangesForDate $listBlocks,
    ) {}

    public function handle(BookingRequestIdData $id, StaffIdData $staff): BookingRequestData
    {
        /** @var User|null $user */
        $user = Auth::user();
        if ($user === null || $user->role !== UserRole::Owner) {
            throw new OwnerRequiredException;
        }

        $this->findStaff->handle($staff);

        return $this->reassign->handle($id, $staff->id, fn (BookingRequestData $booking) => $this->ensureCanTake($staff, $booking));
    }

    private function ensureCanTake(StaffIdData $staff, BookingRequestData $booking): void
    {
        $serviceIds = $booking->service_ids ?? [$booking->service_id];
        if (StaffServiceCoverage::filter([$staff->id], array_map('intval', $serviceIds)) === []) {
            throw new StaffServiceUnavailableException;
        }

        $weekday = CarbonImmutable::createFromFormat('!Y-m-d', $booking->date)?->dayOfWeek;
        /** @var StaffBusinessHourData|null $hours */
        $hours = collect($this->listHours->handle($staff)->all())
            ->first(fn (StaffBusinessHourData $day): bool => $day->weekday === $weekday);
        $startsAt = substr($booking->starts_at, 0, 5);
        $endsAt = substr($booking->ends_at, 0, 5);
        if ($hours === null || ! $hours->is_open || $startsAt < $hours->opens_at || $endsAt > $hours->closes_at) {
            throw new SlotUnavailableException;
        }

        $taken = [...$this->listBlocks->handle($staff, $booking->date), ...$hours->breakRanges()];
        foreach ($taken as [$from, $until]) {
            if ($startsAt < $until && $endsAt > $from) {
                throw new SlotUnavailableException;
            }
        }
    }
}
