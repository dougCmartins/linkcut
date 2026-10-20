<?php

declare(strict_types=1);

namespace Domain\Client\Actions;

use Domain\Client\Data\ClientIdsData;
use Domain\Client\Models\Client;

/** Of these clients, the ones who asked not to receive reminders. */
final class ListOptedOutClients
{
    /** @return list<int> */
    public function handle(ClientIdsData $data): array
    {
        return Client::query()->whereKey($data->ids)->whereNotNull('emails_opted_out_at')->pluck('id')->all();
    }
}
