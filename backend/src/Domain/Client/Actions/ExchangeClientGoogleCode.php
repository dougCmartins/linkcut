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

/**
 * Trades the one-time code for the same session the password login issues. The code dies on read, and only the
 * browser that started the sign-in (same nonce) gets the session: a code link sent to someone else is useless.
 */
final class ExchangeClientGoogleCode
{
    public function __construct(private readonly IssueClientSession $issueSession) {}

    public function handle(GoogleExchangeData $data): ClientSessionData
    {
        $issued = Cache::pull(ClientGoogleLogin::codeKey($data->code));
        $clientId = is_array($issued) ? (int) ($issued['client_id'] ?? 0) : 0;
        $sameBrowser = is_array($issued) && is_string($issued['nonce'] ?? null)
            && hash_equals($issued['nonce'], ClientGoogleLogin::nonceHash($data->nonce));
        if (! $sameBrowser || $clientId <= 0 || ! Client::query()->whereKey($clientId)->exists()) {
            throw new ClientGoogleCodeInvalidException;
        }

        return $this->issueSession->handle(new ClientIdData(client_id: $clientId));
    }
}
