<?php

declare(strict_types=1);

namespace Domain\Client\Actions;

use Domain\Client\Data\ClientIdData;
use Domain\Client\Models\Client;

/** The invitation is sent only once. */
final class MarkClientInvited
{
    public function handle(ClientIdData $data): void
    {
        Client::query()->whereKey($data->client_id)->update(['registration_invited_at' => now()]);
    }
}
