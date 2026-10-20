<?php

declare(strict_types=1);

namespace Domain\Orchestrator\CompleteAppointment\Actions;

use Domain\Finance\Actions\RecordServiceRevenue;
use Domain\Finance\Data\RecordRevenueData;
use Domain\Orchestrator\CompleteAppointment\Data\CompletedAppointmentData;
use Domain\Schedule\Actions\MarkBookingRequestCompleted;
use Domain\Schedule\Data\BookingRequestIdData;
use Domain\Settings\Actions\ShowService;
use Domain\Settings\Data\ServiceIdData;
use Illuminate\Support\Facades\DB;

/**
 * "Concluir atendimento", in one transaction: Schedule marks it concluded (confirmed and today only),
 * Settings gives the service's current price and cost, Finance records them, frozen, in the history.
 */
final class CompleteAppointment
{
    public function __construct(
        private readonly MarkBookingRequestCompleted $markCompleted,
        private readonly ShowService $showService,
        private readonly RecordServiceRevenue $recordRevenue,
    ) {}

    public function handle(BookingRequestIdData $id): CompletedAppointmentData
    {
        return DB::transaction(function () use ($id): CompletedAppointmentData {
            $request = $this->markCompleted->handle($id);
            $service = $this->showService->handle(new ServiceIdData(service_id: $request->service_id));
            $this->recordRevenue->handle(new RecordRevenueData(
                booking_request_id: $request->id,
                date: $request->date,
                time: $request->starts_at,
                client_name: $request->client_name,
                service_name: $request->service_name,
                price_in_cents: $service->price_in_cents,
                cost_in_cents: $service->cost_in_cents,
            ));

            return new CompletedAppointmentData(
                request: $request,
                price_in_cents: $service->price_in_cents,
                cost_in_cents: $service->cost_in_cents,
            );
        });
    }
}
