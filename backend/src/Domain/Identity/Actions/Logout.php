<?php

declare(strict_types=1);

namespace Domain\Identity\Actions;

use Domain\Identity\Data\LogoutData;
use Domain\Identity\Models\RefreshToken;
use Domain\Identity\Models\User;
use Illuminate\Support\Facades\Log;

/** Ends the session: revokes the current access token and the refresh token family. */
final class Logout
{
    public function handle(LogoutData $data): void
    {
        /** @var User $user */
        $user = auth('api')->user();
        $user->token()?->revoke();

        if ($data->refresh_token) {
            $token = RefreshToken::query()
                ->where('user_id', $user->id)
                ->where('token_hash', hash('sha256', $data->refresh_token))
                ->first();
            if ($token) {
                RefreshToken::query()->ofFamily($token->family_id)->active()->update(['revoked_at' => now()]);
            }
        }

        Log::info('user_logged_out', ['user_id' => $user->id]);
    }
}
