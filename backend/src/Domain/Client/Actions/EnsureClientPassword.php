<?php

declare(strict_types=1);

namespace Domain\Client\Actions;

use Domain\Client\Data\ClientPasswordConfirmationData;
use Domain\Client\Models\Client;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/** Asks the password again before something that cannot be undone (deleting the account). */
final class EnsureClientPassword
{
    public function handle(ClientPasswordConfirmationData $data): void
    {
        /** @var Client $client */
        $client = auth('client')->user();
        // A Google-only account has no password to type. The session is the confirmation.
        if (empty($client->password)) {
            return;
        }
        if (! Hash::check((string) $data->password, (string) $client->password)) {
            throw ValidationException::withMessages(['password' => 'current_password']);
        }
    }
}
