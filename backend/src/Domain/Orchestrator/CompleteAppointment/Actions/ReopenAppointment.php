<?php

declare(strict_types=1);

namespace Domain\Orchestrator\CompleteAppointment\Actions;

use Domain\Finance\Actions\RemoveServiceRevenue;
use Domain\Finance\Data\BookingRequestIdData as RevenueRequestIdData;
use Domain\Schedule\Actions\ReopenBookingRequest;
use Domain\Schedule\Data\BookingRequestData;
use Domain\Schedule\Data\BookingRequestIdData;
use Illuminate\Support\Facades\DB;

/** Undo of "Concluir": back to confirmed (Schedule) and out of the history (Finance), together. */
final class ReopenAppointment
{
    public function __construct(
        private readonly ReopenBookingRequest $reopen,
        private readonly RemoveServiceRevenue $removeRevenue,
    ) {}

    public function handle(BookingRequestIdData $id): BookingRequestData
    {
        return DB::transaction(function () use ($id): BookingRequestData {
            $request = $this->reopen->handle($id);
            $this->removeRevenue->handle(new RevenueRequestIdData(booking_request_id: $request->id));

            return $request;
        });
    }
}
