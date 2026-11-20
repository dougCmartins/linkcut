<?php

declare(strict_types=1);

namespace Domain\Orchestrator\CompleteAppointment\Actions;

use Domain\Finance\Actions\RemoveServiceRevenue;
use Domain\Finance\Data\BookingRequestIdData as RevenueRequestIdData;
use Domain\Identity\Enums\UserRole;
use Domain\Identity\Models\User;
use Domain\Schedule\Actions\ReopenBookingRequest;
use Domain\Schedule\Data\BookingRequestData;
use Domain\Schedule\Data\BookingRequestIdData;
use Domain\Team\Exceptions\OwnerRequiredException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/** Undo of "Concluir": owner only (until period closing in P5). */
final class ReopenAppointment
{
    public function __construct(
        private readonly ReopenBookingRequest $reopen,
        private readonly RemoveServiceRevenue $removeRevenue,
    ) {}

    public function handle(BookingRequestIdData $id): BookingRequestData
    {
        /** @var User|null $user */
        $user = Auth::user();
        if ($user === null || $user->role !== UserRole::Owner) {
            throw new OwnerRequiredException;
        }

        return DB::transaction(function () use ($id): BookingRequestData {
            $request = $this->reopen->handle($id);
            $this->removeRevenue->handle(new RevenueRequestIdData(booking_request_id: $request->id));

            return $request;
        });
    }
}
