<?php

declare(strict_types=1);

namespace Domain\Client\Actions;

use Domain\Client\Data\ClientIdsData;
use Domain\Client\Models\Client;

/** One UPDATE per round of invitations (each client is invited only once). */
final class MarkClientsInvited
{
    public function handle(ClientIdsData $data): void
    {
        if ($data->ids === []) {
            return;
        }
        Client::query()->whereKey($data->ids)->update(['registration_invited_at' => now()]);
    }
}
