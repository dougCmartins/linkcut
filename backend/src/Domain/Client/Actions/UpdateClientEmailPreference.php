<?php

declare(strict_types=1);

namespace Domain\Client\Actions;

use Domain\Client\Data\ClientData;
use Domain\Client\Data\ClientEmailPreferenceData;
use Domain\Client\Models\Client;
use Domain\Client\Support\ClientDataMapper;
use Illuminate\Support\Facades\Log;

/** Turns the reminders and invitations back on (or off) from "Minha conta", same flag as the e-mail opt-out link. */
final class UpdateClientEmailPreference
{
    public function handle(ClientEmailPreferenceData $data): ClientData
    {
        /** @var Client $client */
        $client = auth('client')->user();
        $client->update(['emails_opted_out_at' => $data->enabled ? null : ($client->emails_opted_out_at ?? now())]);
        Log::info('client_email_preference_updated', ['client_id' => $client->id, 'enabled' => $data->enabled]);

        return ClientDataMapper::toData($client);
    }
}
