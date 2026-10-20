<?php

declare(strict_types=1);

namespace Domain\Schedule\Actions;

use Carbon\CarbonImmutable;
use Domain\Schedule\Data\BookingRequestData;
use Domain\Schedule\Data\NewBookingRequestData;
use Domain\Schedule\Enums\BookingStatus;
use Domain\Schedule\Exceptions\SlotUnavailableException;
use Domain\Schedule\Models\BookingRequest;
use Domain\Tenant\Context\CurrentTenant;
use Domain\Tenant\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Saves a pending request. Two clients can ask for the same time at once, so the barbershop row is
 * locked while the overlap is checked again: the second one gets SLOT_UNAVAILABLE.
 */
final class CreateBookingRequest
{
    public function __construct(private readonly CurrentTenant $currentTenant) {}

    public function handle(NewBookingRequestData $data): BookingRequestData
    {
        $endsAt = CarbonImmutable::createFromFormat('H:i', $data->starts_at)->addMinutes($data->duration_minutes)->format('H:i');

        $request = DB::transaction(function () use ($data, $endsAt): BookingRequest {
            Tenant::query()->whereKey($this->currentTenant->id())->lockForUpdate()->firstOrFail();
            if (BookingRequest::query()->blocking()->overlapping($data->date, $data->starts_at, $endsAt)->exists()) {
                throw new SlotUnavailableException;
            }

            return BookingRequest::query()->create([
                ...$data->toArray(),
                'ends_at' => $endsAt,
                'status' => BookingStatus::Pending,
            ]);
        });
        Log::info('schedule_booking_requested', ['booking_request_id' => $request->id, 'date' => $request->date]);

        return BookingRequestData::from($request);
    }
}
