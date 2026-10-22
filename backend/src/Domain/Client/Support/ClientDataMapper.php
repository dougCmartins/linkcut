<?php

declare(strict_types=1);

namespace Domain\Client\Support;

use Domain\Client\Data\ClientData;
use Domain\Client\Models\Client;

final class ClientDataMapper
{
    public static function toData(Client $client): ClientData
    {
        return new ClientData(
            id: $client->id,
            name: $client->name,
            email: $client->email,
            phone: $client->phone,
            has_account: $client->hasAccount(),
            emails_enabled: $client->emails_opted_out_at === null,
        );
    }
}
