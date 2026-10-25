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
 * Settings gives the services' costs (and the price, when it was not frozen at booking), Finance records them,
 * frozen, in the history.
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
            // Every service of the visit ("Corte + Barba"); cost and, for older bookings, price are today's.
            $services = collect($request->service_ids ?? [$request->service_id])
                ->map(fn (int $serviceId) => $this->showService->handle(new ServiceIdData(service_id: $serviceId)));
            $priceInCents = $request->price_in_cents ?? (int) $services->sum('price_in_cents');
            $costInCents = (int) $services->sum('cost_in_cents');
            $this->recordRevenue->handle(new RecordRevenueData(
                booking_request_id: $request->id,
                date: $request->date,
                time: $request->starts_at,
                client_name: $request->client_name,
                service_name: mb_substr($request->service_name, 0, 60),
                price_in_cents: $priceInCents,
                cost_in_cents: $costInCents,
            ));

            return new CompletedAppointmentData(
                request: $request,
                price_in_cents: $priceInCents,
                cost_in_cents: $costInCents,
            );
        });
    }
}
