<?php

declare(strict_types=1);

namespace Domain\Client\Actions;

use Domain\Client\Data\ClientIdData;
use Domain\Client\Data\ClientLoginData;
use Domain\Client\Data\ClientSessionData;
use Domain\Client\Exceptions\ClientInvalidCredentialsException;
use Domain\Client\Models\Client;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/** E-mail and password. A client who never completed the sign-up has no password and can't sign in yet. */
final class LoginClient
{
    public function __construct(private readonly IssueClientSession $issueSession) {}

    public function handle(ClientLoginData $data): ClientSessionData
    {
        $client = Client::query()->where('email', mb_strtolower(trim($data->email)))->first();
        if (! $client?->hasAccount() || ! Hash::check($data->password, (string) $client->password)) {
            Log::channel('security')->info('client_login_failed');

            throw new ClientInvalidCredentialsException;
        }

        return $this->issueSession->handle(new ClientIdData(client_id: $client->id));
    }
}
