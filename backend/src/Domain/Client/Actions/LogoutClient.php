<?php

declare(strict_types=1);

namespace Domain\Client\Actions;

use Domain\Client\Data\ClientRefreshData;
use Domain\Client\Models\Client;
use Domain\Client\Models\ClientRefreshToken;
use Illuminate\Support\Facades\Log;

/** Revokes the current access token and the refresh token family. */
final class LogoutClient
{
    public function handle(ClientRefreshData $data): void
    {
        /** @var Client $client */
        $client = auth('client')->user();
        $client->token()?->revoke();
        if ($data->refresh_token) {
            $token = ClientRefreshToken::query()
                ->where('client_id', $client->id)
                ->where('token_hash', hash('sha256', $data->refresh_token))
                ->first();
            if ($token) {
                ClientRefreshToken::query()->ofFamily($token->family_id)->active()->update(['revoked_at' => now()]);
            }
        }
        Log::info('client_logged_out', ['client_id' => $client->id]);
    }
}
