<?php

declare(strict_types=1);

namespace Domain\Client\Actions;

use Domain\Client\Data\ClientData;
use Domain\Client\Models\Client;
use Domain\Client\Support\ClientDataMapper;

final class ShowAuthenticatedClient
{
    public function handle(): ClientData
    {
        /** @var Client $client */
        $client = auth('client')->user();

        return ClientDataMapper::toData($client);
    }
}
