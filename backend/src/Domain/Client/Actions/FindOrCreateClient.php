<?php

declare(strict_types=1);

namespace Domain\Client\Actions;

use Domain\Client\Data\ClientContactData;
use Domain\Client\Data\ClientData;
use Domain\Client\Models\Client;
use Domain\Client\Support\ClientDataMapper;

/**
 * The client of a booking, by e-mail (lowercase). A new e-mail creates a client without password;
 * an existing one keeps its data (a booking form can't change someone's account).
 */
final class FindOrCreateClient
{
    public function handle(ClientContactData $data): ClientData
    {
        $client = Client::query()->firstOrCreate(
            ['email' => mb_strtolower(trim($data->email))],
            ['name' => trim($data->name), 'phone' => $data->phone],
        );

        return ClientDataMapper::toData($client);
    }
}
