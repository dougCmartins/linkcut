<?php

declare(strict_types=1);

namespace Domain\Client\Actions;

use Domain\Client\Data\ClientPasswordChangeData;
use Domain\Client\Models\Client;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * The signed-in client sets a new password after confirming the current one. A wrong current password is a
 * field error (422), not a 401, so the SPA does not take it for an expired session.
 */
final class ChangeClientPassword
{
    public function handle(ClientPasswordChangeData $data): void
    {
        /** @var Client $client */
        $client = auth('client')->user();
        if (! Hash::check($data->current_password, (string) $client->password)) {
            throw ValidationException::withMessages(['current_password' => 'current_password']);
        }
        $client->update(['password' => $data->password]);
        Log::info('client_password_changed', ['client_id' => $client->id]);
    }
}
