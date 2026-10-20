<?php

declare(strict_types=1);

namespace Domain\Client\Actions;

use Domain\Client\Data\ClientIdData;
use Domain\Client\Data\ClientSessionData;
use Domain\Client\Models\Client;
use Domain\Client\Models\ClientRefreshToken;
use Domain\Client\Support\ClientDataMapper;
use Illuminate\Support\Str;

/** Short access token ("client" provider) + opaque refresh token, like the barbers' sessions. */
final class IssueClientSession
{
    public function handle(ClientIdData $data): ClientSessionData
    {
        $client = Client::query()->findOrFail($data->client_id);
        $accessToken = $client->createToken('client-area');
        $refreshToken = Str::random(64);
        $refreshTtlMinutes = (int) config('client.session.refresh_token_ttl_minutes');

        ClientRefreshToken::query()->create([
            'client_id' => $client->id,
            'family_id' => $data->family_id ?: (string) Str::uuid(),
            'token_hash' => hash('sha256', $refreshToken),
            'expires_at' => now()->addMinutes($refreshTtlMinutes),
        ]);

        return new ClientSessionData(
            access_token: (string) $accessToken->accessToken,
            token_type: 'Bearer',
            expires_in: (int) config('client.session.access_token_ttl_minutes') * 60,
            refresh_token: $refreshToken,
            refresh_expires_in: $refreshTtlMinutes * 60,
            client: ClientDataMapper::toData($client),
        );
    }
}
