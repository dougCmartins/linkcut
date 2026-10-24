<?php

declare(strict_types=1);

namespace Domain\Orchestrator\ClientAccount\Actions;

use Domain\Client\Actions\DeleteClient;
use Domain\Client\Actions\EnsureClientPassword;
use Domain\Client\Data\ClientIdData;
use Domain\Client\Data\ClientPasswordConfirmationData;
use Domain\Client\Models\Client;
use Domain\Finance\Actions\AnonymizeRevenueClients;
use Domain\Finance\Data\BookingRequestIdsData;
use Domain\Schedule\Actions\AnonymizeClientBookings;
use Domain\Schedule\Data\ClientBookingsQueryData;
use Illuminate\Support\Facades\DB;

/**
 * "Excluir minha conta" (LGPD): after the password, the personal data leaves every barbershop (Schedule and
 * Finance keep the bookings and amounts, without name or contact) and the account is deleted. All or nothing.
 */
final class DeleteClientAccount
{
    public function __construct(
        private readonly EnsureClientPassword $ensurePassword,
        private readonly AnonymizeClientBookings $anonymizeBookings,
        private readonly AnonymizeRevenueClients $anonymizeRevenue,
        private readonly DeleteClient $deleteClient,
    ) {}

    public function handle(ClientPasswordConfirmationData $data): void
    {
        $this->ensurePassword->handle($data);
        /** @var Client $client */
        $client = auth('client')->user();

        DB::transaction(function () use ($client): void {
            $bookingIds = $this->anonymizeBookings->handle(new ClientBookingsQueryData(client_id: $client->id, email: $client->email));
            $this->anonymizeRevenue->handle(new BookingRequestIdsData(ids: $bookingIds));
            $this->deleteClient->handle(new ClientIdData(client_id: $client->id));
        });
    }
}
