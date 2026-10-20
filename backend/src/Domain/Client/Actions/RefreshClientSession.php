<?php

declare(strict_types=1);

namespace Domain\Client\Actions;

use Domain\Client\Data\ClientIdData;
use Domain\Client\Data\ClientRefreshData;
use Domain\Client\Data\ClientSessionData;
use Domain\Client\Exceptions\ClientInvalidRefreshTokenException;
use Domain\Client\Models\ClientRefreshToken;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/** Rotation: each refresh token works once; a reused one ends the whole session family (it leaked). */
final class RefreshClientSession
{
    public function __construct(private readonly IssueClientSession $issueSession) {}

    public function handle(ClientRefreshData $data): ClientSessionData
    {
        $outcome = DB::transaction(function () use ($data): ClientSessionData|ClientInvalidRefreshTokenException {
            $token = ClientRefreshToken::query()
                ->where('token_hash', hash('sha256', (string) $data->refresh_token))
                ->lockForUpdate()
                ->first();
            if (! $token || $token->expires_at->isPast()) {
                return new ClientInvalidRefreshTokenException;
            }
            if ($token->revoked_at) {
                ClientRefreshToken::query()->ofFamily($token->family_id)->active()->update(['revoked_at' => now()]);
                Log::channel('security')->warning('client_refresh_token_reuse_detected', ['client_id' => $token->client_id]);

                return new ClientInvalidRefreshTokenException;
            }
            $token->update(['revoked_at' => now()]);

            return $this->issueSession->handle(new ClientIdData(client_id: $token->client_id, family_id: $token->family_id));
        });

        if ($outcome instanceof ClientInvalidRefreshTokenException) {
            throw $outcome;
        }

        return $outcome;
    }
}
