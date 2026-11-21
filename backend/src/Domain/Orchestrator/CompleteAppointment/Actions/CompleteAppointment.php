<?php

declare(strict_types=1);

namespace Domain\Orchestrator\CompleteAppointment\Actions;

use Domain\Finance\Actions\RecordCommissionEntry;
use Domain\Finance\Actions\RecordServiceRevenue;
use Domain\Finance\Data\RecordCommissionData;
use Domain\Finance\Data\RecordRevenueData;
use Domain\Identity\Enums\UserRole;
use Domain\Identity\Models\User;
use Domain\Orchestrator\CompleteAppointment\Data\CompletedAppointmentData;
use Domain\Schedule\Actions\MarkBookingRequestCompleted;
use Domain\Schedule\Data\BookingRequestIdData;
use Domain\Schedule\Exceptions\BookingForbiddenException;
use Domain\Schedule\Exceptions\BookingRequestNotFoundException;
use Domain\Schedule\Models\BookingRequest;
use Domain\Settings\Actions\ShowService;
use Domain\Settings\Data\ServiceIdData;
use Domain\Team\Actions\FindStaffByUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * "Concluir atendimento": barber completes own appointments of the day; owner completes any.
 * Also snapshots commission (P5) next to revenue.
 */
final class CompleteAppointment
{
    public function __construct(
        private readonly MarkBookingRequestCompleted $markCompleted,
        private readonly ShowService $showService,
        private readonly RecordServiceRevenue $recordRevenue,
        private readonly RecordCommissionEntry $recordCommission,
        private readonly FindStaffByUser $findStaffByUser,
    ) {}

    public function handle(BookingRequestIdData $id): CompletedAppointmentData
    {
        return DB::transaction(function () use ($id): CompletedAppointmentData {
            $booking = BookingRequest::query()->find($id->id) ?? throw new BookingRequestNotFoundException;
            $this->assertCanManage((int) $booking->staff_id);

            $request = $this->markCompleted->handle($id);

            $services = collect($request->service_ids ?? [$request->service_id])
                ->map(fn (int $serviceId) => $this->showService->handle(new ServiceIdData(service_id: $serviceId)));
            $priceInCents = $request->price_in_cents ?? (int) $services->sum('price_in_cents');
            $costInCents = (int) $services->sum('cost_in_cents');
            $ids = $request->service_ids ?? [$request->service_id];
            $serviceLines = [];
            foreach ($ids as $i => $serviceId) {
                $serviceLines[] = [
                    'service_id' => (int) $serviceId,
                    'catalog_price_in_cents' => $services[$i]->price_in_cents,
                ];
            }

            $this->recordRevenue->handle(new RecordRevenueData(
                booking_request_id: $request->id,
                date: $request->date,
                time: $request->starts_at,
                client_name: $request->client_name,
                service_name: mb_substr($request->service_name, 0, 60),
                price_in_cents: $priceInCents,
                cost_in_cents: $costInCents,
            ));

            $this->recordCommission->handle(new RecordCommissionData(
                booking_request_id: $request->id,
                staff_id: (int) $request->staff_id,
                date: (string) $request->date,
                price_in_cents: $priceInCents,
                cost_in_cents: $costInCents,
                service_lines: $serviceLines,
            ));

            return new CompletedAppointmentData(
                request: $request,
                price_in_cents: $priceInCents,
                cost_in_cents: $costInCents,
            );
        });
    }

    private function assertCanManage(int $staffId): void
    {
        /** @var User|null $user */
        $user = Auth::user();
        if ($user === null) {
            throw new BookingForbiddenException;
        }
        if ($user->role === UserRole::Owner) {
            return;
        }
        $own = $this->findStaffByUser->handle($user->id);
        if ($own === null || $own->id !== $staffId) {
            throw new BookingForbiddenException;
        }
    }
}
