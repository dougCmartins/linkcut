<?php

declare(strict_types=1);

namespace Domain\Client\Actions;

use Domain\Client\Data\ClientData;
use Domain\Client\Data\ClientProfileInputData;
use Domain\Client\Models\Client;
use Domain\Client\Support\ClientDataMapper;
use Domain\Client\Support\PhoneNumber;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/** "Minha conta": the signed-in client changes their name and WhatsApp (used in the next bookings). */
final class UpdateClientProfile
{
    public function handle(ClientProfileInputData $data): ClientData
    {
        $phone = PhoneNumber::digits($data->phone);
        if (! PhoneNumber::isValid($phone)) {
            throw ValidationException::withMessages(['phone' => 'regex']);
        }
        /** @var Client $client */
        $client = auth('client')->user();
        $client->update(['name' => trim($data->name), 'phone' => $phone]);
        Log::info('client_profile_updated', ['client_id' => $client->id]);

        return ClientDataMapper::toData($client);
    }
}
