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
use Illuminate\Support\Str;

/** E-mail and password. A client who never completed the sign-up has no password and can't sign in yet. */
final class LoginClient
{
    public function __construct(private readonly IssueClientSession $issueSession) {}

    public function handle(ClientLoginData $data): ClientSessionData
    {
        $client = Client::query()->where('email', mb_strtolower(trim($data->email)))->first();
        // Always one password check, even without an account: the answer takes the same time either way.
        $passwordMatches = Hash::check($data->password, $client?->hasAccount() ? (string) $client->password : self::dummyHash());
        if (! $client?->hasAccount() || ! $passwordMatches) {
            Log::channel('security')->info('client_login_failed');

            throw new ClientInvalidCredentialsException;
        }

        return $this->issueSession->handle(new ClientIdData(client_id: $client->id));
    }

    private static ?string $dummyHash = null;

    private static function dummyHash(): string
    {
        return self::$dummyHash ??= Hash::make(Str::random(32));
    }
}
