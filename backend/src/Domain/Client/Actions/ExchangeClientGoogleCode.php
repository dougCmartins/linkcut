<?php

declare(strict_types=1);

namespace Domain\Client\Actions;

use Domain\Client\Data\ClientIdData;
use Domain\Client\Data\ClientSessionData;
use Domain\Client\Data\GoogleExchangeData;
use Domain\Client\Exceptions\ClientGoogleCodeInvalidException;
use Domain\Client\Models\Client;
use Domain\Client\Support\ClientGoogleLogin;
use Illuminate\Support\Facades\Cache;

/** Trades the one-time code for the same session the password login issues. The code dies on read. */
final class ExchangeClientGoogleCode
{
    public function __construct(private readonly IssueClientSession $issueSession) {}

    public function handle(GoogleExchangeData $data): ClientSessionData
    {
        $clientId = Cache::pull(ClientGoogleLogin::codeKey($data->code));
        if (! is_numeric($clientId) || (int) $clientId <= 0 || ! Client::query()->whereKey((int) $clientId)->exists()) {
            throw new ClientGoogleCodeInvalidException;
        }

        return $this->issueSession->handle(new ClientIdData(client_id: (int) $clientId));
    }
}
