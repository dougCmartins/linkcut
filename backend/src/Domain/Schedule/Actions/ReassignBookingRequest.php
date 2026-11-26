<?php

declare(strict_types=1);

namespace Domain\Schedule\Actions;

use Closure;
use Domain\Schedule\Data\BookingRequestData;
use Domain\Schedule\Data\BookingRequestIdData;
use Domain\Schedule\Enums\BookingStatus;
use Domain\Schedule\Exceptions\InvalidStatusTransitionException;
use Domain\Schedule\Exceptions\SlotUnavailableException;
use Domain\Schedule\Models\BookingRequest;
use Domain\Tenant\Context\CurrentTenant;
use Domain\Tenant\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Owner moves an appointment not done yet (pending or confirmed) to another barber of the same unit.
 * `$ensureCanTake` runs inside the lock with the booking, for the rules of the other domains (the barber's services,
 * hours, break and blocks); it throws to refuse.
 */
final class ReassignBookingRequest
{
    public function __construct(private readonly CurrentTenant $currentTenant) {}

    /** @param (Closure(BookingRequestData): void)|null $ensureCanTake */
    public function handle(BookingRequestIdData $id, int $staffId, ?Closure $ensureCanTake = null): BookingRequestData
    {
        $request = DB::transaction(function () use ($id, $staffId, $ensureCanTake): BookingRequest {
            Tenant::query()->whereKey($this->currentTenant->id())->lockForUpdate()->firstOrFail(['id']);
            $booking = BookingRequest::query()->whereKey($id->id)->lockForUpdate()->first()
                ?? throw new NotFoundHttpException;
            if (! in_array($booking->status, [BookingStatus::Pending, BookingStatus::Confirmed], true)) {
                throw new InvalidStatusTransitionException;
            }

            if ($booking->staff_id !== $staffId) {
                if ($ensureCanTake !== null) {
                    $ensureCanTake(BookingRequestData::from($booking));
                }
                $overlap = BookingRequest::query()
                    ->blocking()
                    ->where('staff_id', $staffId)
                    ->whereKeyNot($booking->id)
                    ->overlapping($booking->date, $booking->starts_at, $booking->ends_at)
                    ->exists();
                if ($overlap) {
                    throw new SlotUnavailableException;
                }
                $booking->update(['staff_id' => $staffId]);
            }

            return $booking->fresh();
        });

        return BookingRequestData::from($request);
    }
}
