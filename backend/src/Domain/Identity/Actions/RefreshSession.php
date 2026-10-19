<?php

declare(strict_types=1);

namespace Domain\Identity\Actions;

use Domain\Identity\Data\IssueSessionData;
use Domain\Identity\Data\RefreshSessionData;
use Domain\Identity\Data\SessionData;
use Domain\Identity\Exceptions\InvalidRefreshTokenException;
use Domain\Identity\Models\RefreshToken;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Swaps a refresh token for a new session (rotation). A token that was already used
 * means it leaked: the whole family is revoked and the barber must sign in again.
 */
final class RefreshSession
{
    public function __construct(private readonly IssueSession $issueSession) {}

    public function handle(RefreshSessionData $data): SessionData
    {
        $outcome = DB::transaction(function () use ($data): SessionData|InvalidRefreshTokenException {
            $token = RefreshToken::query()
                ->where('token_hash', hash('sha256', $data->refresh_token))
                ->lockForUpdate()
                ->first();

            if (! $token || $token->expires_at->isPast()) {
                return new InvalidRefreshTokenException;
            }
            if ($token->revoked_at) {
                RefreshToken::query()->ofFamily($token->family_id)->active()->update(['revoked_at' => now()]);
                Log::channel('security')->warning('refresh_token_reuse_detected', ['user_id' => $token->user_id]);

                return new InvalidRefreshTokenException;
            }

            $token->update(['revoked_at' => now()]);

            return $this->issueSession->handle(new IssueSessionData(user_id: $token->user_id, family_id: $token->family_id));
        });

        if ($outcome instanceof InvalidRefreshTokenException) {
            throw $outcome;
        }

        return $outcome;
    }
}
