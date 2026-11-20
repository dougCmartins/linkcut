<?php

declare(strict_types=1);

namespace Domain\Schedule\Actions;

use Domain\Schedule\Data\BookingRequestData;
use Domain\Schedule\Data\BookingRequestIdData;
use Domain\Schedule\Exceptions\SlotUnavailableException;
use Domain\Schedule\Models\BookingRequest;
use Domain\Tenant\Context\CurrentTenant;
use Domain\Tenant\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** Owner moves an appointment to another barber of the same unit. */
final class ReassignBookingRequest
{
    public function __construct(private readonly CurrentTenant $currentTenant) {}

    public function handle(BookingRequestIdData $id, int $staffId): BookingRequestData
    {
        $request = DB::transaction(function () use ($id, $staffId): BookingRequest {
            Tenant::query()->whereKey($this->currentTenant->id())->lockForUpdate()->firstOrFail(['id']);
            $booking = BookingRequest::query()->whereKey($id->id)->lockForUpdate()->first()
                ?? throw new NotFoundHttpException;

            if ($booking->staff_id !== $staffId) {
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
