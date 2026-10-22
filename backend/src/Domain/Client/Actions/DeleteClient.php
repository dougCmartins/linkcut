<?php

declare(strict_types=1);

namespace Domain\Client\Actions;

use Domain\Client\Data\ClientIdData;
use Domain\Client\Models\Client;
use Illuminate\Support\Facades\Log;

/** Removes the client's account: sessions, codes and refresh tokens go with it (cascade). */
final class DeleteClient
{
    public function handle(ClientIdData $data): void
    {
        $client = Client::query()->findOrFail($data->client_id);
        $client->tokens()->delete();
        $client->delete();
        Log::info('client_deleted', ['client_id' => $data->client_id]);
    }
}
