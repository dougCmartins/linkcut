<?php

declare(strict_types=1);

namespace Domain\Identity\Actions;

use Domain\Identity\Data\UserIdData;
use Domain\Identity\Models\RefreshToken;
use Domain\Identity\Models\User;
use Illuminate\Support\Facades\Log;

/** Signs a user out of every device: all access tokens and refresh tokens stop working at once. */
final class RevokeUserSessions
{
    public function handle(UserIdData $data): void
    {
        $user = User::query()->find($data->user_id);
        if (! $user) {
            return;
        }
        $user->tokens()->update(['revoked' => true]);
        RefreshToken::query()->where('user_id', $user->id)->active()->update(['revoked_at' => now()]);
        Log::channel('security')->info('user_sessions_revoked', ['user_id' => $user->id]);
    }
}
